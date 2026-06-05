<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate limits in test env (see config/packages/rate_limiter.yaml when@test):
 *   register: 3 requests per hour per IP  (production: 10)
 *   api:      5 requests per minute per user (production: 60)
 *
 * Each test pre-exhausts the limiter via the service and then fires one HTTP
 * request to verify the 429 response — avoiding cross-test Redis pollution.
 */
final class RateLimitingTest extends IntegrationTestCase
{
    public function testRegisterRateLimitBlocksRequestOverThreshold(): void
    {
        $client = static::createClient();

        $registerLimiter = static::getContainer()->get('limiter.register');
        assert($registerLimiter instanceof RateLimiterFactory);
        $limiter = $registerLimiter->create('1.1.1.1');
        $limiter->reset();
        $limiter->consume(3);

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '1.1.1.1'],
            json_encode(['email' => 'reg-over@example.com', 'password' => 'Password1!StrongPass'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(429);
    }

    public function testRegisterRateLimitIsIpScoped(): void
    {
        $client = static::createClient();

        $registerLimiter = static::getContainer()->get('limiter.register');
        assert($registerLimiter instanceof RateLimiterFactory);
        $registerLimiter->create('3.3.3.3')->reset();
        $limiter = $registerLimiter->create('2.2.2.2');
        $limiter->reset();
        $limiter->consume(3);

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '3.3.3.3'],
            json_encode(['email' => 'reg-other-ip@example.com', 'password' => 'Password1!StrongPass'], JSON_THROW_ON_ERROR),
        );

        self::assertNotSame(429, $client->getResponse()->getStatusCode());
    }

    public function testApiRateLimitBlocksRequestOverThreshold(): void
    {
        $client = static::createClient();
        $this->loginUser($client, 'api-limit@example.com', 'secret123');

        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $limiter = $apiLimiter->create('api-limit@example.com');
        $limiter->reset();
        $limiter->consume(5);

        $client->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(429);
    }

    public function testApiRateLimitsUsersIndependently(): void
    {
        $client = static::createClient();

        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create('rate-usera@example.com')->reset();
        $apiLimiter->create('rate-userb@example.com')->reset();

        $this->loginUser($client, 'rate-usera@example.com', 'secret123');
        $apiLimiter->create('rate-usera@example.com')->consume(5);

        $client->request('GET', '/api/me');
        self::assertResponseStatusCodeSame(429);

        $client->request('POST', '/api/logout');
        $this->loginUser($client, 'rate-userb@example.com', 'secret123');
        $client->request('GET', '/api/me');
        self::assertNotSame(429, $client->getResponse()->getStatusCode());
    }

    public function testLoginIsNotBlockedByApiRateLimiter(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => 'login-no-limit@example.com', 'password' => 'secret123']);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login-no-limit@example.com', 'password' => 'secret123'], JSON_THROW_ON_ERROR),
        );

        self::assertNotSame(429, $client->getResponse()->getStatusCode());
    }

    public function testLogoutIsNotBlockedByApiRateLimiter(): void
    {
        $client = static::createClient();
        $this->loginUser($client, 'logout-no-limit@example.com', 'secret123');

        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $limiter = $apiLimiter->create('logout-no-limit@example.com');
        $limiter->reset();
        $limiter->consume(5);

        $client->request('POST', '/api/logout');

        self::assertNotSame(429, $client->getResponse()->getStatusCode());
    }

    private function loginUser(KernelBrowser $client, string $email, string $password): void
    {
        UserFactory::createOne(['email' => $email, 'password' => $password]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
        );
    }
}
