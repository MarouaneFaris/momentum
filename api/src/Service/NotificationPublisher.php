<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

readonly class NotificationPublisher
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {}

    public function publishCreated(Notification $notification): void
    {
        $recipientId = (string) $notification->getRecipient()->getId();
        $this->doPublish(
            "/notifications/{$recipientId}",
            ['op' => 'created', 'notification' => $this->serializeNotification($notification)],
            ['notification_id' => (string) $notification->getId(), 'recipient_id' => $recipientId],
        );
    }

    public function publishUpdated(Notification $notification): void
    {
        $recipientId = (string) $notification->getRecipient()->getId();
        $this->doPublish(
            "/notifications/{$recipientId}",
            ['op' => 'updated', 'notification' => $this->serializeNotification($notification)],
            ['notification_id' => (string) $notification->getId(), 'recipient_id' => $recipientId],
        );
    }

    public function publishDeleted(Uuid $id, User $recipient): void
    {
        $recipientId = (string) $recipient->getId();
        $this->doPublish(
            "/notifications/{$recipientId}",
            ['op' => 'deleted', 'id' => (string) $id],
            ['notification_id' => (string) $id, 'recipient_id' => $recipientId],
        );
    }

    public function publishAllRead(User $recipient, \DateTimeImmutable $readAt): void
    {
        $recipientId = (string) $recipient->getId();
        $this->doPublish(
            "/notifications/{$recipientId}",
            ['op' => 'all-read', 'readAt' => $readAt->format(\DateTimeInterface::ATOM)],
            ['recipient_id' => $recipientId],
        );
    }

    /** @return array<string, mixed> */
    private function serializeNotification(Notification $notification): array
    {
        return [
            'id' => (string) $notification->getId(),
            'type' => $notification->getType()->value,
            'payload' => $notification->getPayload(),
            'readAt' => $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $logContext
     */
    private function doPublish(string $topic, array $data, array $logContext): void
    {
        try {
            $this->hub->publish(new Update(
                topics: [$topic],
                data: json_encode($data, \JSON_THROW_ON_ERROR),
                private: false,
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure publish failed', array_merge($logContext, [
                'topic' => $topic,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'exception' => $e,
            ]));
        }
    }
}
