<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkspaceInvitation>
 */
class WorkspaceInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkspaceInvitation::class);
    }

    /** @return list<WorkspaceInvitation> */
    public function findPendingByWorkspace(Workspace $workspace, \DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('wi')
            ->where('wi.workspace = :workspace')
            ->andWhere('wi.expiresAt > :now')
            ->setParameter('workspace', $workspace)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /** @return list<WorkspaceInvitation> */
    public function findPendingByInvitee(User $user, \DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('wi')
            ->where('wi.invitee = :user')
            ->andWhere('wi.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    public function findByWorkspaceAndInvitee(Workspace $workspace, User $invitee): ?WorkspaceInvitation
    {
        return $this->findOneBy(['workspace' => $workspace, 'invitee' => $invitee]);
    }
}
