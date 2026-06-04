<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<UserProject>
 */
class UserProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProject::class);
    }

    /**
     * @return list<UserProject>
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('up')
            ->where('IDENTITY(up.project) = :projectId')
            ->setParameter('projectId', $project->getId(), UuidType::NAME)
            ->orderBy('up.assignedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByProjectAndUser(Project $project, User $user): ?UserProject
    {
        return $this->findOneBy(['project' => $project, 'user' => $user]);
    }

    public function findOneByProjectAndUserId(Project $project, string $userId): ?UserProject
    {
        return $this->createQueryBuilder('up')
            ->where('IDENTITY(up.project) = :projectId')
            ->andWhere('IDENTITY(up.user) = :userId')
            ->setParameter('projectId', $project->getId(), UuidType::NAME)
            ->setParameter('userId', $userId, UuidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteByUserAndWorkspace(User $user, Workspace $workspace): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $projectIds = $conn->fetchFirstColumn(
            'SELECT id FROM project WHERE workspace_id = :workspaceId',
            ['workspaceId' => $workspace->getId()->toBinary()],
        );

        if ($projectIds === []) {
            return;
        }

        $conn->executeStatement(
            'DELETE FROM user_project WHERE user_id = :userId AND project_id IN (:projectIds)',
            ['userId' => $user->getId()->toBinary(), 'projectIds' => $projectIds],
            ['projectIds' => ArrayParameterType::BINARY],
        );
    }
}
