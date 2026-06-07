<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\NotificationType;
use App\Factory\UserFactory;
use App\Repository\NotificationRepository;
use Symfony\Component\HttpFoundation\Response;

final class DevNotificationTriggerTest extends IntegrationTestCase
{
    public function testUnauthenticatedReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/dev/notifications/trigger',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'task_assigned_to_you'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUnknownTypeReturns422(): void
    {
        $client = static::createClient();
        UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/dev/notifications/trigger',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'not_a_real_type'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTriggerPersistsNotification(): void
    {
        $client = static::createClient();
        $user = UserFactory::createOne(['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->loginAs($client, self::EMAIL, self::PASSWORD);
        $client->request(
            'POST',
            '/api/dev/notifications/trigger',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => NotificationType::TaskAssignedToYou->value], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $repo = static::getContainer()->get(NotificationRepository::class);
        assert($repo instanceof NotificationRepository);
        $notifications = $repo->findAll();
        self::assertCount(1, $notifications);
        self::assertSame(NotificationType::TaskAssignedToYou, $notifications[0]->getType());
    }
}
