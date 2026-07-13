<?php
/**
 * USSD Test Suite — formatted validation report for documentation / screenshots.
 * Run: .\php.bat scripts\run-ussd-test-suite.php
 */
require dirname(__DIR__) . '/tests/bootstrap.php';

$results = runInlineUssdTests();
$passed = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$failed = count(array_filter($results, fn($r) => $r['status'] === 'FAIL'));
$exitCode = $failed > 0 ? 1 : 0;

echo renderReport($results, $passed, $failed, count($results), date('Y-m-d H:i:s T'), $exitCode);
exit($exitCode);

function runInlineUssdTests(): array
{
    $cases = [
        ['TC-U01', 'Registered farmer — main menu displays (CON)', fn() => t01()],
        ['TC-U02', 'Unregistered phone — registration welcome (CON)', fn() => t02()],
        ['TC-U03', 'Registration flow — ward selection list (CON)', fn() => t03()],
        ['TC-U04', 'Invalid main menu option — error handling (END)', fn() => t04()],
        ['TC-U05', 'Advisory option — question prompt (CON)', fn() => t05()],
        ['TC-U06', 'Advisory empty question — validation error (END)', fn() => t06()],
        ['TC-U07', 'Advisory KB answer — within timeout ≤30s & ≤182 chars (END)', fn() => t07()],
        ['TC-U08', 'Weather menu — session completes (END)', fn() => t08()],
        ['TC-U09', 'Officer contact — inline reply on screen (END)', fn() => t09()],
        ['TC-U10', 'My info — farmer profile display (END)', fn() => t10()],
        ['TC-U11', 'Session persistence — ussd_sessions row created', fn() => t11()],
        ['TC-U12', 'Session completion — END marks completed=1', fn() => t12()],
        ['TC-U13', 'Registration error — invalid ward index (END)', fn() => t13()],
        ['TC-U14', 'Security error — unauthorized webhook rejected (END)', fn() => t14()],
        ['TC-U15', 'Multi-step flow — CON prompt then END answer', fn() => t15()],
        ['TC-U16', 'Error recovery — new session after bad input', fn() => t16()],
        ['TC-U17', 'Timeout guard — officer flow instant response <5s', fn() => t17()],
    ];

    $out = [];
    foreach ($cases as [$id, $name, $fn]) {
        try {
            $fn();
            $out[] = ['id' => $id, 'name' => $name, 'status' => 'PASS'];
        } catch (\Throwable $e) {
            $out[] = ['id' => $id, 'name' => $name, 'status' => 'FAIL', 'error' => $e->getMessage()];
        }
    }
    return $out;
}

function ok(bool $c, string $m): void { if (!$c) throw new \RuntimeException($m); }

function t01(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('01'), '0716525852', '');
    ok($r['type'] === 'CON' && str_contains($r['body'], 'Ushauri wa Kilimo'), 'main menu');
}
function t02(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('02'), '0799900100', '');
    ok($r['type'] === 'CON' && str_contains($r['body'], 'Karibu Agri-Advisory'), 'welcome');
}
function t03(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('03'), '0799900100', 'Test Farmer');
    ok($r['type'] === 'CON' && str_contains($r['body'], 'Chagua Kata'), 'wards');
}
function t04(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('04'), '0716525852', '9');
    ok($r['type'] === 'END' && str_contains($r['body'], 'Chaguo batili'), 'invalid menu');
}
function t05(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('05'), '0716525852', '1');
    ok($r['type'] === 'CON' && str_contains($r['body'], 'Ingiza swali'), 'prompt');
}
function t06(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('06'), '0716525852', '1*');
    ok($r['type'] === 'END' && str_contains($r['body'], 'Swali halijawekwa'), 'empty q');
}
function t07(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('07'), '0716525852', '1*Je ni lini bora kupanda mahindi?');
    ok($r['type'] === 'END', 'not END');
    ok($r['elapsed_ms'] <= 30000, 'timeout ' . $r['elapsed_ms'] . 'ms');
    ok($r['length'] <= 182, 'len ' . $r['length']);
}
function t08(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('08'), '0716525852', '2');
    ok($r['type'] === 'END' && str_contains($r['body'], 'Hali ya hewa'), 'weather');
}
function t09(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('09'), '0716525852', '3');
    ok($r['type'] === 'END' && $r['length'] <= 182, 'officer');
}
function t10(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('10'), '0716525852', '4');
    ok($r['type'] === 'END' && str_contains($r['body'], 'Taarifa'), 'info');
}
function t11(): void {
    $s = sid('11'); App\Services\UssdTestHarness::dispatch($s, '0716525852', '');
    ok(App\Services\UssdTestHarness::sessionRow($s) !== null, 'no session row');
}
function t12(): void {
    $s = sid('12'); App\Services\UssdTestHarness::dispatch($s, '0716525852', '4');
    $row = App\Services\UssdTestHarness::sessionRow($s);
    ok($row && (int)$row['completed'] === 1, 'not completed');
}
function t13(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('13'), '0799900100', 'Ali Juma*999');
    ok($r['type'] === 'END' && str_contains($r['body'], 'Chaguo batili'), 'bad ward');
}
function t14(): void {
    putenv('AT_SKIP_WEBHOOK_VERIFY=0'); putenv('AT_API_KEY=test-key');
    $r = App\Services\UssdTestHarness::dispatch(sid('14'), '0716525852', '');
    putenv('AT_SKIP_WEBHOOK_VERIFY=1');
    ok(str_contains($r['body'], 'Unauthorized'), 'auth');
}
function t15(): void {
    $s = sid('15');
    ok(App\Services\UssdTestHarness::dispatch($s, '0716525852', '1')['type'] === 'CON', 'step1');
    ok(App\Services\UssdTestHarness::dispatch($s, '0716525852', '1*Ni lini kupanda mahindi?')['type'] === 'END', 'step2');
}
function t16(): void {
    App\Services\UssdTestHarness::dispatch(sid('16a'), '0716525852', '99');
    ok(App\Services\UssdTestHarness::dispatch(sid('16b'), '0716525852', '')['type'] === 'CON', 'recovery');
}
function t17(): void {
    $r = App\Services\UssdTestHarness::dispatch(sid('17'), '0716525852', '3');
    ok($r['elapsed_ms'] <= 5000, 'slow ' . $r['elapsed_ms'] . 'ms');
}

function sid(string $n): string { return App\Services\UssdTestHarness::uniqueSessionId('u' . $n); }

function renderReport(array $results, int $passed, int $failed, int $total, string $now, int $exitCode): string
{
    $w = 74;
    $L = [];
    $L[] = str_repeat('=', $w);
    $L[] = pad('AGRI-ADVISORY USSD TEST SUITE - VALIDATION REPORT', $w);
    $L[] = pad('Session Flow | Timeout | Error Handling Scenarios', $w);
    $L[] = str_repeat('=', $w);
    $L[] = '  Generated : ' . $now;
    $L[] = '  Stack     : PHP ' . PHP_VERSION . ' | MySQL ' . (getenv('DB_NAME') ?: 'agridb');
    $L[] = str_repeat('-', $w);
    $L[] = '  ALL CORE SESSION FLOW TEST CASES';
    $L[] = str_repeat('-', $w);

    foreach ($results as $r) {
        $tag = $r['status'] === 'PASS' ? '[PASS]' : '[FAIL]';
        $line = sprintf('  %s  %-7s  %s', $tag, $r['id'], $r['name']);
        if ($r['status'] === 'FAIL' && !empty($r['error'])) {
            $line .= ' -> ' . $r['error'];
        }
        $L[] = $line;
    }

    $L[] = str_repeat('-', $w);
    $L[] = '  TIMEOUT & ERROR HANDLING (subset highlighted)';
    $L[] = str_repeat('-', $w);
    foreach ($results as $r) {
        if (!preg_match('/timeout|error|invalid|recovery|empty|unauthorized|security/i', $r['name'])) {
            continue;
        }
        $tag = $r['status'] === 'PASS' ? '[PASS]' : '[FAIL]';
        $L[] = sprintf('  %s  %-7s  %s', $tag, $r['id'], $r['name']);
    }

    $L[] = str_repeat('=', $w);
    $L[] = sprintf('  SUMMARY: %d passed | %d failed | %d total', $passed, $failed, $total);
    $L[] = '  Overall : ' . ($exitCode === 0 ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED');
    $L[] = str_repeat('=', $w);

    return implode("\n", $L) . "\n";
}

function pad(string $t, int $w): string
{
    $p = max(0, (int) floor(($w - strlen($t)) / 2));
    return str_repeat(' ', $p) . $t;
}
