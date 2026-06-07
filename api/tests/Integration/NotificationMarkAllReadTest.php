<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\NotificationFactory;
use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class NotificationMarkAllReadTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();

        $client->request('PATCH', '/api/notifications/read-all');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMarksAllUnreadNotificationsRead(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        NotificationFactory::createMany(3, ['recipient' => $user, 'readAt' => null]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/read-all');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/notifications');
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        foreach ($data as $item) {
            self::assertNotNull($item['readAt']);
        }
    }

    public function testIdempotentOnAlreadyRead(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $readAt = new \DateTimeImmutable('2026-01-01T00:00:00Z');

        NotificationFactory::createMany(2, ['recipient' => $user, 'readAt' => $readAt]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/read-all');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDoesNotAffectOtherUsersNotifications(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $other = UserFactory::createOne();

        NotificationFactory::createMany(2, ['recipient' => $other, 'readAt' => null]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('PATCH', '/api/notifications/read-all');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $otherNotifications = NotificationFactory::repository()->findBy(['recipient' => $other]);
        foreach ($otherNotifications as $n) {
            self::assertNull($n->getReadAt());
        }
    }
}
