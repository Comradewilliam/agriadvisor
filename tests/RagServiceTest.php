<?php

namespace Tests;

use App\Services\RagService;
use PHPUnit\Framework\TestCase;

class RagServiceTest extends TestCase
{
    private RagService $rag;

    protected function setUp(): void
    {
        $this->rag = new RagService();
    }

    public function testIsAgriculturalQuerySwahiliKeywords(): void
    {
        $this->assertTrue($this->rag->isAgriculturalQuery('Ninawezaje kupanda mahindi msimu huu?'));
        $this->assertTrue($this->rag->isAgriculturalQuery('Mbolea gani ni bora kwa maharage?'));
    }

    public function testIsNotAgriculturalQuery(): void
    {
        $this->assertFalse($this->rag->isAgriculturalQuery('Nani rais wa Tanzania?'));
        $this->assertFalse($this->rag->isAgriculturalQuery('Bei ya gari ni ngapi?'));
    }

    public function testSearchReturnsEmptyForNonsense(): void
    {
        $results = $this->rag->search('xyzqwerty nonsense query 12345');
        $this->assertIsArray($results);
    }
}
