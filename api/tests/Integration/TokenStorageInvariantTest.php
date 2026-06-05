<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\UserFactory;
use App\Repository\AuthTokenRepository;
use App\Service\AuthTokenManager;

/**
 * ADR-008 enforcement: the raw auth token must never be persisted — only its SHA-256 hash.
 */
final class TokenStorageInvariantTest extends IntegrationTestCase
{
    protected const string PASSWORD = 'secret123';

    public function testLoginPersistsExactlyOneAuthToken(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        $repo = static::getContainer()->get(AuthTokenRepository::class);
        assert($repo instanceof AuthTokenRepository);
        self::assertCount(1, $repo->findAll());
    }

    public function testStoredTokenIsHashNotPlaintext(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        $rawToken = $client->getCookieJar()->get(AuthTokenManager::COOKIE_NAME)?->getValue();
        self::assertNotNull($rawToken, 'auth_token cookie missing from login response');

        $repo = static::getContainer()->get(AuthTokenRepository::class);
        assert($repo instanceof AuthTokenRepository);
        $stored = $repo->findAll();
        self::assertCount(1, $stored);

        $storedHash = $stored[0]->getToken();
        self::assertNotSame($rawToken, $storedHash, 'Raw token must not be stored in plaintext');
        self::assertSame(hash('sha256', $rawToken), $storedHash, 'Stored token must equal SHA-256 hash of raw token');
    }
}
