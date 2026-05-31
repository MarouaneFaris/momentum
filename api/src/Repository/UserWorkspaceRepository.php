<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<UserWorkspace>
 */
class UserWorkspaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserWorkspace::class);
    }

    /**
     * @return list<UserWorkspace>
     */
    public function findByUser(User $user): array
    {
        return $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT uw, w FROM %s uw JOIN uw.workspace w WHERE IDENTITY(uw.user) = :userId ORDER BY w.name ASC',
                    UserWorkspace::class,
                ),
            )
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->getResult();
    }

    public function findRoleByUserAndWorkspace(User $user, Workspace $workspace): ?WorkspaceRole
    {
        return $this->findOneBy(['user' => $user, 'workspace' => $workspace])?->getRole();
    }

    public function findOneByWorkspaceAndUserId(Workspace $workspace, string $userId): ?UserWorkspace
    {
        return $this->createQueryBuilder('uw')
            ->where('IDENTITY(uw.workspace) = :workspaceId')
            ->andWhere('IDENTITY(uw.user) = :userId')
            ->setParameter('workspaceId', $workspace->getId(), UuidType::NAME)
            ->setParameter('userId', $userId, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<UserWorkspace>
     */
    public function findMembersByWorkspace(Workspace $workspace): array
    {
        return $this->createQueryBuilder('uw')
            ->where('IDENTITY(uw.workspace) = :workspaceId')
            ->setParameter('workspaceId', $workspace->getId(), UuidType::NAME)
            ->orderBy('uw.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
