<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;

interface NotificationServiceInterface
{
    /** @param array<string, mixed> $payload */
    public function create(User $recipient, NotificationType $type, array $payload): Notification;
}
