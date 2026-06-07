<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

final class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NotificationRepository $notificationRepository,
    ) {}

    /** @param array<string, mixed> $payload */
    public function create(User $recipient, NotificationType $type, array $payload): Notification
    {
        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setType($type);
        $notification->setPayload($payload);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }

    public function markRead(Notification $notification): void
    {
        if ($notification->isRead()) {
            return;
        }

        $notification->setReadAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function markAllRead(User $recipient): void
    {
        $this->notificationRepository->markAllReadForRecipient($recipient);
    }

    public function delete(Notification $notification): void
    {
        $this->em->remove($notification);
        $this->em->flush();
    }
}
