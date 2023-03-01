<?php
namespace TalentlmsIntegrationTests\Validations;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TalentlmsIntegration\Validations\TLMSFloat;

/**
 * @covers TalentlmsIntegration\Validations\TLMSFloat
 */
class FloatTest extends TestCase
{

    public function testFloatHappyPath(): void
    {
        $float = (new TLMSFloat(5))->getValue();
        $this->assertIsFloat($float);
        $this->assertEquals(5.0, $float);
    }

    public function testFloatPassingZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSFloat(0))->getValue();
    }

    public function testFloatPassingNegative(): void
    {
        $float = (new TLMSFloat(-1))->getValue();
        $this->assertIsFloat($float);
        $this->assertEquals(-1.0, $float);
    }

    public function testFloatPassingNumberAsString(): void
    {
        $float = (new TLMSFloat('-1'))->getValue();
        $this->assertIsFloat($float);
        $this->assertEquals(-1.0, $float);
    }

    public function testFloatPassingString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSFloat('ABCDEFG'))->getValue();
    }

    public function testFloatPassingNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSFloat(null))->getValue();
    }

    public function testFloatPassingEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSFloat(''))->getValue();
    }
}
