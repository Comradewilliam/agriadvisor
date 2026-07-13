<?php

use App\Services\UssdTestHarness;
use PHPUnit\Framework\TestCase;

/**
 * USSD session flow validation — core menus, registration, timeout & error paths.
 */
class UssdSessionFlowTest extends TestCase
{
    private const REGISTERED_PHONE = '0716525852';
    private const UNREGISTERED_PHONE = '0799900100';
    private const USSD_MAX_CHARS = 182;
    private const USSD_TIMEOUT_MS = 30000;

    protected function setUp(): void
    {
        putenv('AT_SKIP_WEBHOOK_VERIFY=1');
    }

    public function test_tc_u01_registered_farmer_main_menu(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u01');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '');

        $this->assertSame('CON', $r['type']);
        $this->assertStringContainsString('Ushauri wa Kilimo', $r['body']);
        $this->assertStringContainsString('Hali ya Hewa', $r['body']);
    }

    public function test_tc_u02_unregistered_welcome_prompt(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u02');
        $r = UssdTestHarness::dispatch($sid, self::UNREGISTERED_PHONE, '');

        $this->assertSame('CON', $r['type']);
        $this->assertStringContainsString('Karibu Agri-Advisory', $r['body']);
        $this->assertStringContainsString('majina yako', $r['body']);
    }

    public function test_tc_u03_registration_ward_list(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u03');
        $r = UssdTestHarness::dispatch($sid, self::UNREGISTERED_PHONE, 'Test Farmer');

        $this->assertSame('CON', $r['type']);
        $this->assertStringContainsString('Chagua Kata', $r['body']);
    }

    public function test_tc_u04_invalid_main_menu_option(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u04');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '9');

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Chaguo batili', $r['body']);
    }

    public function test_tc_u05_advisory_question_prompt(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u05');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '1');

        $this->assertSame('CON', $r['type']);
        $this->assertStringContainsString('Ingiza swali', $r['body']);
    }

    public function test_tc_u06_advisory_empty_question_error(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u06');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '1*');

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Swali halijawekwa', $r['body']);
    }

    public function test_tc_u07_advisory_kb_answer_within_timeout(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u07');
        $r = UssdTestHarness::dispatch(
            $sid,
            self::REGISTERED_PHONE,
            '1*Je ni lini bora kupanda mahindi?'
        );

        $this->assertSame('END', $r['type']);
        $this->assertLessThanOrEqual(self::USSD_TIMEOUT_MS, $r['elapsed_ms'], 'Advisory exceeded USSD timeout budget');
        $this->assertLessThanOrEqual(self::USSD_MAX_CHARS, $r['length'], 'Response exceeds USSD screen limit');
        $this->assertNotEmpty(trim(str_replace('END', '', $r['body'])));
    }

    public function test_tc_u08_weather_menu(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u08');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '2');

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Hali ya hewa', $r['body']);
    }

    public function test_tc_u09_officer_contact(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u09');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '3');

        $this->assertSame('END', $r['type']);
        $this->assertLessThanOrEqual(self::USSD_MAX_CHARS, $r['length']);
    }

    public function test_tc_u10_my_info(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u10');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '4');

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Taarifa', $r['body']);
        $this->assertStringContainsString('0716525852', $r['body']);
    }

    public function test_tc_u11_session_persisted_on_start(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u11');
        UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '');

        $row = UssdTestHarness::sessionRow($sid);
        $this->assertNotNull($row);
        $this->assertSame($sid, $row['session_id']);
    }

    public function test_tc_u12_session_marked_completed_on_end(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u12');
        UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '4');

        $row = UssdTestHarness::sessionRow($sid);
        $this->assertNotNull($row);
        $this->assertSame(1, (int)$row['completed']);
        $this->assertNotEmpty($row['end_time']);
    }

    public function test_tc_u13_registration_invalid_ward(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u13');
        $r = UssdTestHarness::dispatch($sid, self::UNREGISTERED_PHONE, 'Ali Juma*999');

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Chaguo batili', $r['body']);
    }

    public function test_tc_u14_unauthorized_webhook_rejected(): void
    {
        putenv('AT_SKIP_WEBHOOK_VERIFY=0');
        $_ENV['AT_SKIP_WEBHOOK_VERIFY'] = '0';
        putenv('AT_API_KEY=test-key-not-empty');

        $sid = UssdTestHarness::uniqueSessionId('u14');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '');

        putenv('AT_SKIP_WEBHOOK_VERIFY=1');
        $_ENV['AT_SKIP_WEBHOOK_VERIFY'] = '1';

        $this->assertSame('END', $r['type']);
        $this->assertStringContainsString('Unauthorized', $r['body']);
    }

    public function test_tc_u15_con_session_continues_after_menu(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u15');
        $step1 = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '1');
        $step2 = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '1*Ni lini kupanda mahindi?');

        $this->assertSame('CON', $step1['type']);
        $this->assertSame('END', $step2['type']);
    }

    public function test_tc_u16_error_recovery_new_session(): void
    {
        $bad = UssdTestHarness::uniqueSessionId('u16a');
        UssdTestHarness::dispatch($bad, self::REGISTERED_PHONE, '99');

        $good = UssdTestHarness::uniqueSessionId('u16b');
        $r = UssdTestHarness::dispatch($good, self::REGISTERED_PHONE, '');

        $this->assertSame('CON', $r['type']);
        $this->assertStringContainsString('Ushauri wa Kilimo', $r['body']);
    }

    public function test_tc_u17_officer_flow_within_timeout(): void
    {
        $sid = UssdTestHarness::uniqueSessionId('u17');
        $r = UssdTestHarness::dispatch($sid, self::REGISTERED_PHONE, '3');

        $this->assertSame('END', $r['type']);
        $this->assertLessThanOrEqual(5000, $r['elapsed_ms'], 'Officer lookup should be instant (no AI)');
    }
}
