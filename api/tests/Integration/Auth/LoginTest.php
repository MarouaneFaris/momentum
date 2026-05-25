<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Factory\UserFactory;
use App\Service\AuthTokenManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class LoginTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    public function testValidCredentialsReturn200(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'user@test.com', 'password' => 'secret123']);

        $client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@test.com', 'password' => 'secret123']),
        );

        self::assertResponseStatusCodeSame(200);
    }

    public function testValidLoginSetsAuthTokenCookie(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'user@test.com', 'password' => 'secret123']);

        $client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@test.com', 'password' => 'secret123']),
        );

        $cookies = $client->getResponse()->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame(AuthTokenManager::COOKIE_NAME, $cookies[0]->getName());
        self::assertNotEmpty($cookies[0]->getValue());
    }

    public function testLoginCookieHasSecurityFlags(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'user@test.com', 'password' => 'secret123']);

        $client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@test.com', 'password' => 'secret123']),
        );

        $cookie = $client->getResponse()->headers->getCookies()[0];
        self::assertTrue($cookie->isHttpOnly(), 'Cookie must be HttpOnly');
        self::assertTrue($cookie->isSecure(), 'Cookie must be Secure');
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite(), 'Cookie must have SameSite=Strict');
    }

    public function testWrongPasswordReturns401(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'user@test.com', 'password' => 'correct-password']);

        $client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'user@test.com', 'password' => 'wrong-password']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testUnknownEmailReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => 'nobody@test.com', 'password' => 'any-password']),
        );

        self::assertResponseStatusCodeSame(401);
    }
}
