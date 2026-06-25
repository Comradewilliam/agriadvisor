<?php
namespace App\Services;

use App\Helpers\Logger;

/**
 * AI chat via OpenRouter (OpenAI-compatible API).
 * @see https://openrouter.ai/docs
 */
class OpenAIService {
    private string $apiKey;
    private string $model;
    private string $fallbackModel;
    private string $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    private const MAX_ATTEMPTS = 3;
    private const REQUEST_TIMEOUT = 90;

    public function __construct() {
        $config = require __DIR__ . '/../../config.php';
        $this->apiKey = $config['apis']['openrouter']['api_key'] ?? '';
        $this->model = $config['apis']['openrouter']['model'] ?? 'google/gemma-4-26b-a4b-it:free';
        $this->fallbackModel = $config['apis']['openrouter']['fallback_model'] ?? '';
    }

    public function generateResponse($systemPrompt, $userMessage) {
        return $this->generateWithHistory($systemPrompt, [
            ['role' => 'user', 'content' => $userMessage],
        ]);
    }

    /**
     * @param array<int, array{role: string, content: string}> $historyMessages
     * @param array{timeout?: int, max_attempts?: int, allow_fallback?: bool, allow_retry?: bool} $opts
     */
    public function generateWithHistory(string $systemPrompt, array $historyMessages, int $maxTokens = 400, array $opts = []): array
    {
        if (!$this->apiKey) {
            Logger::error('OpenRouter API key missing');
            return ['confidence' => 'low', 'response' => null, 'error' => 'missing_api_key'];
        }

        $timeout = max(8, (int)($opts['timeout'] ?? self::REQUEST_TIMEOUT));
        $maxAttempts = max(1, (int)($opts['max_attempts'] ?? self::MAX_ATTEMPTS));
        $allowFallback = (bool)($opts['allow_fallback'] ?? true);
        $allowRetry = (bool)($opts['allow_retry'] ?? true);

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $historyMessages
        );

        $data = [
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => 0.3,
        ];

        $models = array_values(array_unique(array_filter([
            $this->model,
            $this->fallbackModel,
        ])));
        if (!$allowFallback) {
            $models = array_slice($models, 0, 1);
        }

        $last = ['confidence' => 'low', 'response' => null, 'error' => 'unknown'];

        foreach ($models as $modelIndex => $model) {
            $data['model'] = $model;
            $attempts = $allowRetry
                ? ($modelIndex === 0 ? $maxAttempts : min(2, $maxAttempts))
                : 1;

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                $last = $this->callApi($data, $timeout);

                if (!empty($last['response'])) {
                    $last['confidence'] = $this->detectConfidence($last['response']);
                    if ($modelIndex > 0 || $attempt > 1) {
                        Logger::info('OpenRouter succeeded after retry/fallback', [
                            'model'   => $model,
                            'attempt' => $attempt,
                        ]);
                    }
                    return $last;
                }

                if (!$allowRetry || $attempt >= $attempts || !$this->shouldRetry($last)) {
                    break;
                }

                $wait = $this->retryDelaySeconds($last, $attempt);
                Logger::info('OpenRouter retrying', [
                    'model'     => $model,
                    'attempt'   => $attempt + 1,
                    'wait_sec'  => $wait,
                    'http_code' => $last['http_code'] ?? null,
                ]);
                sleep($wait);
            }

            if ($allowFallback && $modelIndex < count($models) - 1) {
                Logger::info('OpenRouter trying fallback model', ['fallback' => $models[$modelIndex + 1]]);
            }
        }

        return $last;
    }

    /** @return array{confidence?: string, response: ?string, error?: string, http_code?: int, retry_after?: int} */
    private function callApi(array $data, int $timeout = self::REQUEST_TIMEOUT): array
    {
        $appUrl = getenv('APP_URL') ?: 'http://localhost:1234';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: ' . $appUrl,
            'X-Title: Agri-Advisory',
        ];

        $ch = \curl_init($this->endpoint);
        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        ];
        if (getenv('AT_SSL_VERIFY') === '0' || getenv('AT_SSL_VERIFY') === 'false'
            || PHP_OS_FAMILY === 'Windows' || str_contains(getenv('APP_URL') ?: '', 'ngrok')) {
            $curlOpts[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        curl_setopt_array($ch, $curlOpts);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('OpenRouter curl error', ['error' => $error, 'model' => $data['model'] ?? '']);
            $isTimeout = str_contains(strtolower($error), 'timed out');
            return [
                'confidence' => 'low',
                'response'   => null,
                'error'      => $isTimeout ? 'timeout' : $error,
                'http_code'  => 0,
            ];
        }

        $result = json_decode((string)$response, true);
        if ($httpCode !== 200) {
            Logger::error('OpenRouter API error', [
                'http_code' => $httpCode,
                'model'     => $data['model'] ?? '',
                'body'      => mb_substr((string)$response, 0, 500),
            ]);
            $retryAfter = (int)($result['error']['metadata']['retry_after_seconds'] ?? 0);
            return [
                'confidence'  => 'low',
                'response'    => null,
                'error'       => 'api_error',
                'http_code'   => $httpCode,
                'retry_after' => $retryAfter,
            ];
        }

        $content = trim($result['choices'][0]['message']['content'] ?? '');
        if ($content === '') {
            Logger::warning('OpenRouter empty content', ['model' => $data['model'] ?? '']);
            return ['confidence' => 'low', 'response' => null, 'error' => 'empty_response', 'http_code' => 200];
        }

        return ['confidence' => 'high', 'response' => $content];
    }

    private function shouldRetry(array $result): bool
    {
        $code = (int)($result['http_code'] ?? 0);
        $error = (string)($result['error'] ?? '');

        if ($code === 429 || in_array($code, [502, 503, 529], true)) {
            return true;
        }
        if ($error === 'timeout' || $error === 'empty_response') {
            return true;
        }
        if (str_contains(strtolower($error), 'timed out')) {
            return true;
        }

        return false;
    }

    private function retryDelaySeconds(array $result, int $attempt): int
    {
        if ((int)($result['http_code'] ?? 0) === 429) {
            $fromApi = (int)($result['retry_after'] ?? 0);
            if ($fromApi > 0) {
                return min($fromApi, 12);
            }
            return min(4 + ($attempt * 3), 12);
        }
        return min(2 + ($attempt * 2), 8);
    }

    private function detectConfidence(string $content): string
    {
        $uncertainPhrases = ['sijui', "i don't know", 'sina uhakika', 'tafadhali wasiliana'];
        foreach ($uncertainPhrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                return 'low';
            }
        }
        return 'high';
    }
}
