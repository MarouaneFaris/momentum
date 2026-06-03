<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return list<Project>
     */
    public function findVisibleForMember(Workspace $workspace, UserWorkspace $callerMembership): array
    {
        $role = $callerMembership->getRole();

        if ($role === WorkspaceRole::Guest) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->where('IDENTITY(p.workspace) = :workspaceId')
            ->setParameter('workspaceId', $workspace->getId(), UuidType::NAME)
            ->orderBy('p.createdAt', 'ASC');

        if ($role === WorkspaceRole::Member) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'p.status != :draft',
                    'IDENTITY(p.owner) = :ownerId',
                )
            )
                ->setParameter('draft', ProjectStatus::Draft->value)
                ->setParameter('ownerId', $callerMembership->getId(), UuidType::NAME);
        }

        return $qb->getQuery()->getResult();
    }
}
