<?php

namespace Tests;

use App\Helpers\Sanitizer;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    public function testStringStripsTagsAndLimitsLength(): void
    {
        $result = Sanitizer::string('<script>alert(1)</script>Hello World', 5);
        $this->assertSame('Hello', $result);
    }

    public function testPhoneNormalizesDigits(): void
    {
        $this->assertSame('+255712345678', Sanitizer::phone('+255 712 345 678'));
    }

    public function testIntReturnsNullForInvalid(): void
    {
        $this->assertNull(Sanitizer::int('abc'));
        $this->assertSame(42, Sanitizer::int('42'));
    }
}
