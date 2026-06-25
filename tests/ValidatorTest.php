<?php

namespace Tests;

use App\Helpers\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredFields(): void
    {
        $v = Validator::make(['email' => '']);
        $this->assertFalse($v->validate(['email' => 'required|email']));
        $this->assertArrayHasKey('email', $v->errors());
    }

    public function testValidEmailPasses(): void
    {
        $v = Validator::make(['email' => 'farmer@example.com']);
        $this->assertTrue($v->validate(['email' => 'required|email']));
    }

    public function testPhoneValidation(): void
    {
        $v = Validator::make(['phone' => '+255712345678']);
        $this->assertTrue($v->validate(['phone' => 'required|phone']));
    }

    public function testInRule(): void
    {
        $v = Validator::make(['role' => 'dao']);
        $this->assertTrue($v->validate(['role' => 'required|in:dao,ward_officer']));
        $v2 = Validator::make(['role' => 'admin']);
        $this->assertFalse($v2->validate(['role' => 'required|in:dao,ward_officer']));
    }
}
