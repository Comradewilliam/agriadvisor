<?php
require dirname(__DIR__) . '/app/Core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
$config = require dirname(__DIR__) . '/config.php';
$key = $config['apis']['openrouter']['api_key'];
$model = $config['apis']['openrouter']['model'];
$data = json_encode(['model'=>$model,'messages'=>[['role'=>'user','content'=>'hi']],'max_tokens'=>20]);
$ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>1,CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$data,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$key],CURLOPT_SSL_VERIFYPEER=>0]);
$r = curl_exec($ch);
echo curl_getinfo($ch,CURLINFO_HTTP_CODE) . "\n" . $r;
