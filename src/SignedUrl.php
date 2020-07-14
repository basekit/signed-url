<?php
namespace BaseKit\SignedUrl;

use DateTime;
use DateTimeInterface;
use League\Uri\Components\Query;
use League\Uri\Http;
use League\Uri\UriModifier;
use BaseKit\SignedUrl\Exceptions\MissingSignatureKey;
use BaseKit\SignedUrl\Exceptions\ReservedParameter;

class SignedUrl
{
    protected $signatureKey;
    protected $expiresParameter;
    protected $signatureParameter;

    public function __construct(string $signatureKey, string $expiresParameter = 'expires', string $signatureParameter = 'signature')
    {
        if (empty($signatureKey)) {
            throw new MissingSignatureKey('The signature key is empty');
        }

        $this->signatureKey = $signatureKey;
        $this->expiresParameter = $expiresParameter;
        $this->signatureParameter = $signatureParameter;
    }

    public function sign(string $url, \DateTimeInterface $expiry): string
    {
        $url = Http::createFromString($url);
        $this->validateUrlDoesNotContainReservedParameters($url);

        $signature = $this->createSignature($url, $expiry);
        return $this->signUrl($url, $expiry, $signature);
    }

    public function validate(string $url): bool
    {
        $url = Http::createFromString($url);
        return $this->hasCorrectSignature($url)
            && $this->signatureHasNotExpired($url);
    }

    private function hasCorrectSignature(Http $url): bool
    {
        $query = Query::createFromUri($url);
        $signatureParameter = $query->get($this->signatureParameter);
        $urlWithoutSignature = UriModifier::removeParams($url, $this->signatureParameter);
        $signature = $this->generateSignature((string)$urlWithoutSignature);
        return hash_equals($signature, (string)$signatureParameter);
    }

    private function signatureHasNotExpired(Http $url): bool
    {
        $query = Query::createFromUri($url);
        $expiresParameter = $query->get($this->expiresParameter);
        return $this->getCurrentTimestamp() < $expiresParameter;
    }

    private function createSignature(Http $url, DateTimeInterface $expiration): string
    {
        $url = UriModifier::appendQuery($url, $this->expiresParameter.'=' . $expiration->format('U'));
        return $this->generateSignature((string)$url);
    }

    private function signUrl(Http $url, DateTimeInterface $expiration, string $signature): string
    {
        $url = UriModifier::appendQuery($url, $this->expiresParameter.'=' . $expiration->format('U') . '&'.$this->signatureParameter.'=' . $signature);
        return (string)$url;
    }

    private function generateSignature(string $url): string
    {
        return hash_hmac('sha256', $url, $this->signatureKey);
    }

    private function validateUrlDoesNotContainReservedParameters(Http $url): void
    {
        $query = Query::createFromUri($url);
        if ($query->has($this->expiresParameter)) {
            throw new ReservedParameter(
                'Your URL contains '. $this->expiresParameter . '
                which is a reserved parameter. This can be overridden in constructor'
            );
        }

        if ($query->has($this->signatureParameter)) {
            throw new ReservedParameter(
                'Your URL contains '. $this->signatureParameter . '
                which is a reserved parameter. This can be overridden in constructor'
            );
        }
    }

    private function getCurrentTimestamp(): string
    {
        return (DateTime::createFromFormat('U', (string) time()))->format('U');
    }
}
