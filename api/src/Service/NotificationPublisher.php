<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class NotificationPublisher
{
    public function __construct(
        private HubInterface $hub,
    ) {}

    public function publish(Notification $notification): void
    {
        $recipientId = (string) $notification->getRecipient()->getId();

        $this->hub->publish(new Update(
            topics: ["/notifications/{$recipientId}"],
            data: json_encode([
                'id' => (string) $notification->getId(),
                'type' => $notification->getType()->value,
                'payload' => $notification->getPayload(),
                'readAt' => $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
                'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], \JSON_THROW_ON_ERROR),
            private: false,
        ));
    }
}
