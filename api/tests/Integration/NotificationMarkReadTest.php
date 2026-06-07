<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\NotificationFactory;
use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class NotificationMarkReadTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $notification = NotificationFactory::createOne();

        $client->request('PATCH', '/api/notifications/' . $notification->getId() . '/read');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testOtherUserNotificationReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $other = UserFactory::createOne();
        $notification = NotificationFactory::createOne(['recipient' => $other]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/' . $notification->getId() . '/read');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMarkReadSetsReadAt(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $notification = NotificationFactory::createOne(['recipient' => $user, 'readAt' => null]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/' . $notification->getId() . '/read');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNotNull($data['readAt']);
    }

    public function testAlreadyReadIsIdempotent(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $readAt = new \DateTimeImmutable('2026-01-01T00:00:00Z');
        $notification = NotificationFactory::createOne(['recipient' => $user, 'readAt' => $readAt]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/' . $notification->getId() . '/read');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame($readAt->format(\DateTimeInterface::ATOM), $data['readAt']);
    }

    public function testNonExistentReturns404(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/00000000-0000-0000-0000-000000000000/read');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
