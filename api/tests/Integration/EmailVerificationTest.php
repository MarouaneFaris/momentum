<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\EmailVerificationToken;
use App\Factory\UserFactory;
use App\Message\SendVerificationEmail;
use App\Service\AuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class EmailVerificationTest extends IntegrationTestCase
{
    private function transport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');

        return $transport;
    }

    /** @return list<\Symfony\Component\Messenger\Envelope> */
    private function sentMessages(): array
    {
        return array_values(iterator_to_array($this->transport()->get()));
    }

    // ── Registration dispatches message ─────────────────────────────────────

    public function testRegisterDispatchesSendVerificationEmailMessage(): void
    {
        $client = static::createClient();

        $limiter = static::getContainer()->get('limiter.register');
        assert($limiter instanceof RateLimiterFactory);
        $limiter->create('10.0.0.1')->reset();

        $client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '10.0.0.1'],
            json_encode(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $messages = $this->sentMessages();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendVerificationEmail::class, $messages[0]->getMessage());
    }

    // ── Login guard ─────────────────────────────────────────────────────────

    public function testUnverifiedUserLoginReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD, 'emailVerifiedAt' => null]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('EMAIL_NOT_VERIFIED', $body['code']);
    }

    public function testVerifiedUserLoginReturns200(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD, 'emailVerifiedAt' => new \DateTimeImmutable()]);

        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // ── Verify email endpoint ────────────────────────────────────────────────

    public function testVerifyEmailWithValidTokenSets200AndMarksVerified(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'emailVerifiedAt' => null]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setExpiresAt(new \DateTimeImmutable('+24 hours'));
        $em->persist($token);
        $em->flush();

        $client->request('GET', '/api/verify-email?token=' . $rawToken);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $em->clear();
        $refreshed = $em->find(\App\Entity\User::class, $user->getId());
        self::assertNotNull($refreshed?->getEmailVerifiedAt());
    }

    public function testVerifyEmailWithInvalidTokenReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/verify-email?token=invalidtoken');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('EMAIL_TOKEN_INVALID', $body['code']);
    }

    public function testVerifyEmailWithExpiredTokenReturns400(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['emailVerifiedAt' => null]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $em->persist($token);
        $em->flush();

        $client->request('GET', '/api/verify-email?token=' . $rawToken);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('EMAIL_TOKEN_EXPIRED', $body['code']);
    }

    public function testVerifyEmailWithUsedTokenReturns400(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['emailVerifiedAt' => null]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setExpiresAt(new \DateTimeImmutable('+24 hours'));
        $token->setUsedAt(new \DateTimeImmutable());
        $em->persist($token);
        $em->flush();

        $client->request('GET', '/api/verify-email?token=' . $rawToken);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('EMAIL_TOKEN_INVALID', $body['code']);
    }

    public function testVerifyEmailMissingTokenReturns400(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/verify-email');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ── Resend verification ──────────────────────────────────────────────────

    public function testResendVerificationDispatchesMessageForUnverifiedUser(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'emailVerifiedAt' => null]);

        $client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $messages = $this->sentMessages();
        self::assertCount(1, $messages);
        self::assertInstanceOf(SendVerificationEmail::class, $messages[0]->getMessage());
    }

    public function testResendVerificationDoesNotDispatchForVerifiedUser(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'emailVerifiedAt' => new \DateTimeImmutable()]);

        $client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertCount(0, $this->transport()->get());
    }

    public function testResendVerificationDoesNotLeakUserExistence(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@example.com'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // ── Full flow ────────────────────────────────────────────────────────────

    public function testFullVerificationFlow(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD, 'emailVerifiedAt' => null]);

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = AuthTokenManager::hashToken($rawToken);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $token = new EmailVerificationToken();
        $token->setUser($user);
        $token->setTokenHash($tokenHash);
        $token->setExpiresAt(new \DateTimeImmutable('+24 hours'));
        $em->persist($token);
        $em->flush();

        // Login blocked before verification
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Verify
        $client->request('GET', '/api/verify-email?token=' . $rawToken);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        // Login succeeds after verification
        $client->request(
            'POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => self::EMAIL, 'password' => self::PASSWORD], JSON_THROW_ON_ERROR),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
