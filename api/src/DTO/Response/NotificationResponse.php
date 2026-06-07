<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Notification;
use OpenApi\Attributes as OA;

final readonly class NotificationResponse
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string', example: 'task_assigned_to_you')]
        public string $type,
        #[OA\Property(type: 'object')]
        public array $payload,
        #[OA\Property(type: 'string', format: 'date-time', nullable: true)]
        public ?string $readAt,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $createdAt,
    ) {}

    public static function fromNotification(Notification $notification): self
    {
        return new self(
            id: (string) $notification->getId(),
            type: $notification->getType()->value,
            payload: $notification->getPayload(),
            readAt: $notification->getReadAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $notification->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
