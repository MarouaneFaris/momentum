<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class HealthCheckTest extends IntegrationTestCase
{
    public function testReturnsOkWhenAllDependenciesHealthy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(200);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(['status' => 'ok'], $data);
    }

    public function testReturnsDegradedWhenRedisFails(): void
    {
        $client = static::createClient();

        static::getContainer()->set('app.redis_connection', new \Redis());

        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(503);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(['status' => 'degraded'], $data);
    }

    public function testReturnsDegradedWhenDbFails(): void
    {
        $client = static::createClient();

        $badConn = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 1,
            'user' => 'x',
            'password' => 'x',
            'dbname' => 'x',
            'serverVersion' => 'mariadb-11.4.0',
        ]);
        static::getContainer()->set('doctrine.dbal.default_connection', $badConn);

        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(503);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame(['status' => 'degraded'], $data);
    }

    public function testHealthEndpointIsNotRateLimited(): void
    {
        $client = static::createClient();

        $apiLimiter = static::getContainer()->get('limiter.api');
        assert($apiLimiter instanceof RateLimiterFactory);
        $apiLimiter->create('probe@railway.app')->consume(5);

        for ($i = 0; $i < 10; ++$i) {
            $client->request('GET', '/api/health');
            self::assertNotSame(429, $client->getResponse()->getStatusCode());
        }
    }

    public function testHealthEndpointIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertNotSame(401, $client->getResponse()->getStatusCode());
        self::assertNotSame(403, $client->getResponse()->getStatusCode());
    }
}
