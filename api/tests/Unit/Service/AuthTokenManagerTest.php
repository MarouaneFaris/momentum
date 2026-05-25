<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\AuthTokenManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthTokenManagerTest extends TestCase
{
    public function testHashTokenReturnsSha256(): void
    {
        $raw = 'some-raw-token';
        $expected = hash('sha256', $raw);

        self::assertSame($expected, AuthTokenManager::hashToken($raw));
    }

    public function testHashTokenIsDeterministic(): void
    {
        $raw = 'deterministic-input';

        self::assertSame(
            AuthTokenManager::hashToken($raw),
            AuthTokenManager::hashToken($raw),
        );
    }

    public function testHashTokenDifferentInputsProduceDifferentOutputs(): void
    {
        self::assertNotSame(
            AuthTokenManager::hashToken('token-a'),
            AuthTokenManager::hashToken('token-b'),
        );
    }

    public function testCreateClearCookieIsHttpOnly(): void
    {
        $cookie = AuthTokenManager::createClearCookie();

        self::assertTrue($cookie->isHttpOnly());
    }

    public function testCreateClearCookieIsSecure(): void
    {
        $cookie = AuthTokenManager::createClearCookie();

        self::assertTrue($cookie->isSecure());
    }

    public function testCreateClearCookieSameSiteIsStrict(): void
    {
        $cookie = AuthTokenManager::createClearCookie();

        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    public function testCreateClearCookieExpiryIsInThePast(): void
    {
        $cookie = AuthTokenManager::createClearCookie();

        self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
    }
}
