<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\UserFactory;
use App\Service\AuthTokenManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class LoginTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    private const string EMAIL = 'user@example.com';
    private const string PASSWORD = 'secret123';

    public function testValidCredentialsReturn200(): void
    {
        $this->loginAs(self::EMAIL, self::PASSWORD);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testSuccessfulLoginSetsAuthTokenCookie(): void
    {
        $client = $this->loginAs(self::EMAIL, self::PASSWORD);

        $cookie = $this->findAuthCookie($client);
        self::assertNotNull($cookie, sprintf('Expected "%s" cookie in response.', AuthTokenManager::COOKIE_NAME));
        self::assertNotEmpty($cookie->getValue());
    }

    public function testAuthCookieIsHttpOnly(): void
    {
        $client = $this->loginAs(self::EMAIL, self::PASSWORD);

        $cookie = $this->findAuthCookie($client);
        self::assertNotNull($cookie, sprintf('Expected "%s" cookie in response.', AuthTokenManager::COOKIE_NAME));
        self::assertTrue($cookie->isHttpOnly());
    }

    public function testAuthCookieIsSecure(): void
    {
        $client = $this->loginAs(self::EMAIL, self::PASSWORD);

        $cookie = $this->findAuthCookie($client);
        self::assertNotNull($cookie, sprintf('Expected "%s" cookie in response.', AuthTokenManager::COOKIE_NAME));
        self::assertTrue($cookie->isSecure());
    }

    public function testAuthCookieSameSiteIsStrict(): void
    {
        $client = $this->loginAs(self::EMAIL, self::PASSWORD);

        $cookie = $this->findAuthCookie($client);
        self::assertNotNull($cookie, sprintf('Expected "%s" cookie in response.', AuthTokenManager::COOKIE_NAME));
        self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
    }

    public function testWrongPasswordReturns401(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => 'wrong-password'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUnknownEmailReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com', 'password' => 'any-password'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function loginAs(string $email, string $password): KernelBrowser
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => $email, 'password' => $password]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );

        return $client;
    }

    private function findAuthCookie(KernelBrowser $client): ?Cookie
    {
        foreach ($client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === AuthTokenManager::COOKIE_NAME) {
                return $cookie;
            }
        }

        return null;
    }
}
