<?php
namespace TalentlmsIntegrationTests\Validations;

use TalentlmsIntegration\Validations\TLMSPositiveInteger;

/**
 * @covers TalentlmsIntegration\Validations\TLMSPositiveInteger
 */
class PositiveIntegerTest extends \PHPUnit\Framework\TestCase
{

    public function testPositiveIntegerHappyPath(): void
    {
        $positiveInteger = (new TLMSPositiveInteger(5))->getValue();
        $this->assertIsInt($positiveInteger);
        $this->assertEquals(5, $positiveInteger);
    }

    public function testPositiveIntegerHappyPathBigInteger(): void
    {
        $positiveInteger = (new TLMSPositiveInteger(555555555555555))->getValue();
        $this->assertIsInt($positiveInteger);
        $this->assertEquals(555555555555555, $positiveInteger);
    }

    public function testPositiveIntegerPassingZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger(0))->getValue();
    }

    public function testPositiveIntegerPassingNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
         (new TLMSPositiveInteger(-1))->getValue();
    }

    public function testPositiveIntegerPassingNumberAsString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger('-1'))->getValue();
    }

    public function testPositiveIntegerPassingString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger('ABCDEFG'))->getValue();
    }

    public function testPositiveIntegerPassingNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger(null))->getValue();
    }

    public function testPositiveIntegerPassingEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger(''))->getValue();
    }

    public function testPositiveIntegerPassingEmptyFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSPositiveInteger(3.5))->getValue();
    }
}
