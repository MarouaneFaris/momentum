<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\NotificationType;
use App\Factory\NotificationFactory;
use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class NotificationListTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/notifications');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsOnlyOwnNotifications(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $other = UserFactory::createOne();

        NotificationFactory::createMany(3, ['recipient' => $user, 'type' => NotificationType::TaskAssignedToYou]);
        NotificationFactory::createMany(2, ['recipient' => $other, 'type' => NotificationType::InvitationReceived]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/notifications');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertCount(3, $data);
        foreach ($data as $item) {
            self::assertSame(NotificationType::TaskAssignedToYou->value, $item['type']);
        }
    }

    public function testReturnsNewestFirst(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $old = NotificationFactory::createOne(['recipient' => $user, 'type' => NotificationType::TaskAssignedToYou]);
        $new = NotificationFactory::createOne(['recipient' => $user, 'type' => NotificationType::InvitationReceived]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/notifications');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertCount(2, $data);
        self::assertSame((string) $new->getId(), $data[0]['id']);
        self::assertSame((string) $old->getId(), $data[1]['id']);
    }

    public function testReadAtNullForUnread(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        NotificationFactory::createOne(['recipient' => $user, 'readAt' => null]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/notifications');

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertNull($data[0]['readAt']);
    }

    public function testEmptyListForNoNotifications(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('GET', '/api/notifications');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }
}
