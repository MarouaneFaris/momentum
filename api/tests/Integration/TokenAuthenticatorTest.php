<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\AuthTokenFactory;
use App\Factory\UserFactory;
use App\Service\AuthTokenManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class TokenAuthenticatorTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected function tearDown(): void
    {
        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create('user@example.com')->reset();

        parent::tearDown();
    }

    public function testValidTokenGrantsAccess(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'user@example.com', 'password' => 'secret123']);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'user@example.com', 'password' => 'secret123'], JSON_THROW_ON_ERROR),
        );

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testRequestWithoutCookieReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testInvalidTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/me',
            [],
            [],
            ['HTTP_COOKIE' => AuthTokenManager::COOKIE_NAME . '=invalid-garbage-token'],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testExpiredTokenReturns401(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne();
        $rawToken = bin2hex(random_bytes(32));

        AuthTokenFactory::createOne([
            'token' => AuthTokenManager::hashToken($rawToken),
            'user' => $user,
            'expiresAt' => new \DateTimeImmutable('-1 day'),
        ]);

        $client->request(
            'GET',
            '/api/me',
            [],
            [],
            ['HTTP_COOKIE' => AuthTokenManager::COOKIE_NAME . '=' . $rawToken],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
