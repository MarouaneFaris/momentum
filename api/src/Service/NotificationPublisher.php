<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

readonly class NotificationPublisher
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {}

    public function publish(Notification $notification): void
    {
        $recipientId = (string) $notification->getRecipient()->getId();
        $topic = "/notifications/{$recipientId}";

        try {
            $this->hub->publish(new Update(
                topics: [$topic],
                data: json_encode([
                    'id' => (string) $notification->getId(),
                    'type' => $notification->getType()->value,
                    'payload' => $notification->getPayload(),
                    'readAt' => $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
                    'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ], \JSON_THROW_ON_ERROR),
                private: false,
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure publish failed', [
                'notification_id' => (string) $notification->getId(),
                'recipient_id' => $recipientId,
                'topic' => $topic,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
