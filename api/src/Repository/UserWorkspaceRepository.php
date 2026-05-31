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
        /** @var list<UserWorkspace> $result */
        $result = $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT uw, w FROM %s uw JOIN uw.workspace w WHERE IDENTITY(uw.user) = :userId ORDER BY w.name ASC',
                    UserWorkspace::class,
                ),
            )
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->getResult();

        return $result;
    }

    public function findRoleByUserAndWorkspace(User $user, Workspace $workspace): ?WorkspaceRole
    {
        $userWorkspace = $this->findOneBy(['user' => $user, 'workspace' => $workspace]);

        return $userWorkspace?->getRole();
    }
}
