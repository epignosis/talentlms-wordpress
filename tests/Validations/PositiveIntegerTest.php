<?php

use TalentlmsIntegration\Validations\PositiveInteger;

class PositiveIntegerTest extends \PHPUnit\Framework\TestCase{

	public function testPositiveIntegerHappyPath(): void
    {
        $positiveInteger = (new PositiveInteger(5))->getValue();
		$this->assertIsInt($positiveInteger);
    }

	public function testPositiveIntegerHappyPathBigInteger(): void
    {
        $positiveInteger = (new PositiveInteger(555555555555555))->getValue();
		$this->assertIsInt($positiveInteger);
    }

	public function testPositiveIntegerPassingZero(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger(0))->getValue();
    }

	public function testPositiveIntegerPassingNegative(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger(-1))->getValue();
    }

	public function testPositiveIntegerPassingNumberAsString(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger('-1'))->getValue();
    }

	public function testPositiveIntegerPassingString(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger('ABCDEFG'))->getValue();
    }

	public function testPositiveIntegerPassingNull(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger(null))->getValue();
    }

	public function testPositiveIntegerPassingEmptyString(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger(''))->getValue();
    }

	public function testPositiveIntegerPassingEmptyFloat(): void
    {
		$this->expectException(InvalidArgumentException::class);
        $positiveInteger = (new PositiveInteger(3.5))->getValue();
    }
}