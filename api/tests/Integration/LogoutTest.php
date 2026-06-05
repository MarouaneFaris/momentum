<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\UserFactory;
use App\Repository\AuthTokenRepository;
use App\Service\AuthTokenManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class LogoutTest extends IntegrationTestCase
{
    public function testLogoutReturns204(): void
    {
        $client = static::createClient();
        $this->loginUser($client);

        $client->request('POST', '/api/logout');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testLogoutDeletesTokenFromDb(): void
    {
        $client = static::createClient();
        $this->loginUser($client);

        $repo = static::getContainer()->get(AuthTokenRepository::class);
        assert($repo instanceof AuthTokenRepository);
        self::assertCount(1, $repo->findAll());

        $client->request('POST', '/api/logout');

        self::assertCount(0, $repo->findAll());
    }

    public function testLogoutClearsCookie(): void
    {
        $client = static::createClient();
        $this->loginUser($client);

        $client->request('POST', '/api/logout');

        $cookie = null;
        foreach ($client->getResponse()->headers->getCookies() as $c) {
            if ($c->getName() === AuthTokenManager::COOKIE_NAME) {
                $cookie = $c;
                break;
            }
        }

        self::assertNotNull($cookie, sprintf('Expected "%s" cookie in logout response.', AuthTokenManager::COOKIE_NAME));
        self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
    }

    public function testRevokedTokenReturns401(): void
    {
        $client = static::createClient();
        $this->loginUser($client);

        $rawToken = $client->getCookieJar()->get(AuthTokenManager::COOKIE_NAME)?->getValue();
        self::assertNotNull($rawToken, 'Expected auth_token cookie after login.');

        $client->request('POST', '/api/logout');

        $client->request(
            'GET',
            '/api/me',
            [],
            [],
            ['HTTP_COOKIE' => AuthTokenManager::COOKIE_NAME . '=' . $rawToken],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function loginUser(KernelBrowser $client): void
    {
        UserFactory::createOne(['email' => 'user@example.com', 'password' => 'secret123']);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'user@example.com', 'password' => 'secret123'], JSON_THROW_ON_ERROR),
        );
    }
}
