<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Factory\NotificationFactory;
use App\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class NotificationDeleteTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();
        $notification = NotificationFactory::createOne();

        $client->request('DELETE', '/api/notifications/' . $notification->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testOtherUserNotificationReturns403(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $other = UserFactory::createOne();
        $notification = NotificationFactory::createOne(['recipient' => $other]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/notifications/' . $notification->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOwnerCanDelete(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $notification = NotificationFactory::createOne(['recipient' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/notifications/' . $notification->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDeletedNotificationNoLongerInList(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);
        $notification = NotificationFactory::createOne(['recipient' => $user]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/notifications/' . $notification->getId());

        $client->request('GET', '/api/notifications');
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame([], $data);
    }

    public function testNonExistentReturns404(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request('DELETE', '/api/notifications/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
