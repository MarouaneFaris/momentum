<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] */
    public function findByRecipient(User $recipient): array
    {
        return $this->createQueryBuilder('n')
            ->where('IDENTITY(n.recipient) = :recipientId')
            ->setParameter('recipientId', $recipient->getId(), UuidType::NAME)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function markAllReadForRecipient(User $recipient): void
    {
        $recipientId = $recipient->getId();
        if ($recipientId === null) {
            return;
        }

        // Intentional raw DBAL bulk UPDATE — avoids loading all entities into the UoW
        // for a single-shot write path. DQL UPDATE would have the same UoW semantics here.
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE notification SET read_at = NOW() WHERE recipient_id = :recipientId AND read_at IS NULL',
            ['recipientId' => $recipientId->toBinary()],
        );
    }
}
