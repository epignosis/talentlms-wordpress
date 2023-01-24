<?php
namespace TalentlmsIntegrationTests\Validations;

use TalentlmsIntegration\Validations\TLMSEmail;

/**
 * @covers TalentlmsIntegration\Validations\TLMSEmail
 */
class EmailTest extends \PHPUnit\Framework\TestCase
{

    public function testEmailHappyPath(): void
    {
        $email = (new TLMSEmail('helloworld@example.com'))->getValue();
        $this->assertEquals('helloworld@example.com', $email);
    }

    public function testEmailHappyPathMoreThanOneDomainExt(): void
    {
        $email = (new TLMSEmail('helloworld@example.com.fr'))->getValue();
        $this->assertEquals('helloworld@example.com.fr', $email);
    }

    public function testEmailMissingAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSEmail('helloworld.example.com'))->getValue();
    }

    public function testEmailMissingDomainExt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSEmail('helloworld@example'))->getValue();
    }

    public function testEmailInvalidDomainExt1(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSEmail('helloworld@example.com.gr_'))->getValue();
    }

    public function testEmailInvalidDomainExt2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSEmail('helloworld@example.com.gr@'))->getValue();
    }

    public function testEmailInvalidAt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSEmail('hello@world#example.com.gr'))->getValue();
    }
}
