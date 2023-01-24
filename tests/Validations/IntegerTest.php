<?php

use TalentlmsIntegration\Validations\TLMSInteger;

/**
 * @covers TalentlmsIntegration\Validations\TLMSInteger
 */
class IntegerTest extends \PHPUnit\Framework\TestCase
{

    public function testIntegerHappyPath(): void
    {
        $integer = (new TLMSInteger(5))->getValue();
        $this->assertIsInt($integer);
        $this->assertEquals(5, $integer);
    }

    public function testIntegerHappyPathBigInteger(): void
    {
        $integer = (new TLMSInteger(555555555555555))->getValue();
        $this->assertIsInt($integer);
        $this->assertEquals(555555555555555, $integer);
    }

    public function testIntegerHappyPathZero(): void
    {
        $integer = (new TLMSInteger(0))->getValue();
        $this->assertIsInt($integer);
        $this->assertEquals(0, $integer);
    }

    public function testIntegerZeroAsString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $integer = (new TLMSInteger('0'))->getValue();
    }

    public function testIntegerHappyPathPassingNegative(): void
    {
        $integer = (new TLMSInteger(-1))->getValue();
        $this->assertIsInt($integer);
        $this->assertEquals(-1, $integer);
    }

    public function testIntegerPassingString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSInteger('ABCDEFG'))->getValue();
    }

    public function testIntegerPassingNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSInteger(null))->getValue();
    }

    public function testIntegerPassingEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSInteger(''))->getValue();
    }

    public function testIntegerPassingEmptyFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSInteger(3.5))->getValue();
    }
}
