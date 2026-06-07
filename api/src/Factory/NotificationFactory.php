<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Notification;
use App\Enum\NotificationType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Notification>
 */
final class NotificationFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Notification::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'recipient' => UserFactory::new(),
            'type' => NotificationType::TaskAssignedToYou,
            'payload' => [
                'task_id' => self::faker()->uuid(),
                'task_title' => self::faker()->sentence(4),
            ],
            'readAt' => null,
        ];
    }
}
