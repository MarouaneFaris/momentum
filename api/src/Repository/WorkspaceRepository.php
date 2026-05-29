<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workspace>
 */
class WorkspaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workspace::class);
    }

    /**
     * @return list<array{workspace: Workspace, role: WorkspaceRole}>
     */
    public function findByUser(User $user): array
    {
        /** @var list<UserWorkspace> $memberships */
        $memberships = $this->getEntityManager()
            ->createQuery(
                sprintf(
                    'SELECT uw, w FROM %s uw JOIN uw.workspace w WHERE uw.user = :user ORDER BY w.name ASC',
                    UserWorkspace::class,
                ),
            )
            ->setParameter('user', $user)
            ->getResult();

        return array_map(
            static fn(UserWorkspace $uw) => ['workspace' => $uw->getWorkspace(), 'role' => $uw->getRole()],
            $memberships,
        );
    }
}
