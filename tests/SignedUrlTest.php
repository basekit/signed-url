<?php
namespace BaseKit\SignedUrl\Tests;

use DateTime;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use BaseKit\SignedUrl\Exceptions\MissingSignatureKey;
use BaseKit\SignedUrl\Exceptions\ReservedParameter;
use BaseKit\SignedUrl\SignedUrl;

class SignedUrlTest extends TestCase
{
    use PHPMock;

    /** @test */
    public function it_throws_an_exception_when_signature_missing()
    {
        $this->expectException(MissingSignatureKey::class);
        new SignedUrl('');
    }

    public function reservedParameterProvider()
    {
        return [
            ['http://dev.app/?expires=3883797rw9e'],
            ['http://dev.app/?signature=fshsdfhdsfhsdkfhkjdh'],
        ];
    }

    /**
     * @test
     * @dataProvider reservedParameterProvider
     * @param string $url
     */
    public function it_throws_an_exception_when_a_reserved_parameter_is_in_url(string $url)
    {
        $this->expectException(ReservedParameter::class);
        $urlSigner = new SignedUrl('honey-badger');
        $expiry = new DateTime("+ 10 days");
        $urlSigner->sign($url, $expiry);
    }

    /** @test */
    public function it_signs_a_url_and_returns_as_string()
    {
        $urlSigner = new SignedUrl('honey-badger');
        $expiry = new DateTime("+ 10 days");
        $url = $urlSigner->sign('http://dev.app', $expiry);
        $this->assertIsString($url);
    }

    /** @test */
    public function it_validates_a_newly_signed_url()
    {
        $urlSigner = new SignedUrl('honey-badger');
        $expiry = new DateTime("+ 10 days");
        $url = $urlSigner->sign('http://dev.app', $expiry);

        $urlSignValidator = new SignedUrl('honey-badger');
        $this->assertTrue($urlSignValidator->validate($url));
    }

    /** @test */
    public function it_overrides_expiry_and_signature_params_when_set_in_constructor()
    {
        $urlSigner = new SignedUrl('honey-badger', 'expirationParam', 'secureSignature');
        $expiry = new DateTime("+ 10 days");
        $url = $urlSigner->sign('http://dev.app', $expiry);
        $this->assertStringContainsString('expirationParam=', $url);
        $this->assertStringContainsString('secureSignature=', $url);

        $urlSignValidator = new SignedUrl('honey-badger', 'expirationParam', 'secureSignature');
        $this->assertTrue($urlSignValidator->validate($url));
    }


    /** @test */
    public function it_does_not_validate_a_signed_url_with_different_signature_key()
    {
        $urlSigner = new SignedUrl('honey-badger');
        $expiry = new DateTime("+ 10 days");
        $url = $urlSigner->sign('http://dev.app', $expiry);

        $urlSignValidator = new SignedUrl('alligator-snapping-turtle');
        $this->assertFalse($urlSignValidator->validate($url));
    }

    public function unsignedUrlProvider()
    {
        return [
            ['http://dev.app/'],
            ['http://dev.app/?expires=1594799585'],
            ['http://dev.app/?signature=41d5c3a92c6ef94e73cb70c7dcda0859'],
        ];
    }

    /**
     * @test
     * @dataProvider unsignedUrlProvider
     * @param string $unsignedUrl
     */
    public function it_does_not_validate_an_unsigned_url(string $unsignedUrl)
    {
        $urlSigner = new SignedUrl('honey-badger');
        $this->assertFalse($urlSigner->validate($unsignedUrl));
    }

    /**
     * @test
     * @dataProvider expiryAndNowStringsProvider
     * @runInSeparateProcess
     * @param string $now
     * @param string $expiry
     */
    public function it_does_not_validate_a_signed_url_with_an_expired_expiry(string $now, string $expiry)
    {
        $urlSigner = new SignedUrl('honey-badger');
        $expiry = new DateTime($expiry);
        $url = $urlSigner->sign('http://dev.app', $expiry);

        $urlSigner = new SignedUrl('honey-badger');
        $time = $this->getFunctionMock('BaseKit\SignedUrl', "time");
        $time->expects($this->atLeastOnce())->willReturn((new \DateTimeImmutable($now))->format('U'));
        $this->assertFalse($urlSigner->validate($url));
    }

    public function expiryAndNowStringsProvider()
    {
        return [
            ['+11 days', '+10 days'],
            ['+2 hour', '+1 hour'],
        ];
    }

    /** @test */
    public function it_does_not_validate_an_expired_signed_url()
    {
        $expiredUrl = "http://dev.app?expires=1593936178&signature=f06fd78962661beb58cb679b6768e85588ca9e5737ac73d9aaa349b2407a8f19";
        $urlSigner = new SignedUrl('honey-badger');
        $this->assertFalse($urlSigner->validate($expiredUrl));
    }

    public function originalUrlProvider()
    {
        return [
            ['http://dev.app/foo/bar/baz'],
            ['http://dev.app/?query=string'],
            ['http://dev.app/foo/bar/baz?query=string'],
        ];
    }

    /**
     * @test
     * @dataProvider originalUrlProvider
     * @param string $originalUrl
     */
    public function it_does_not_break_existing_url_structure(string $originalUrl)
    {
        $urlSigner = new SignedUrl('honey-badger');

        $expiry = new DateTime("+ 10 days");
        $url = $urlSigner->sign($originalUrl, $expiry);
        $this->assertStringStartsWith($originalUrl, $url);

    }
}

