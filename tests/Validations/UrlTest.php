<?php
namespace TalentlmsIntegrationTests\Validations;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TalentlmsIntegration\Validations\TLMSUrl;

/**
 * @covers TalentlmsIntegration\Validations\TLMSUrl
 */
class UrlTest extends TestCase
{

    public function testUrlHappyPath(): void
    {
        $url = (new TLMSUrl('http://www.talentlms.com'))->getValue();
        $this->assertEquals('http://www.talentlms.com', $url);
    }

    public function testUrlHappyPathMissingWWW(): void
    {
        $url = (new TLMSUrl('http://talentlms.com'))->getValue();
        $this->assertEquals('http://talentlms.com', $url);
    }

    public function testUrlMissingHttp(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSUrl('www.talentlms.com'))->getValue();
    }

    public function testUrlMalformed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSUrl('http//www.talentlms.com'))->getValue();
    }

    public function testUrlMalformed1(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSUrl('http:/www.talentlms.com'))->getValue();
    }

    public function testUrlMalformed2(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TLMSUrl('http:www.talentlms.com'))->getValue();
    }
}
