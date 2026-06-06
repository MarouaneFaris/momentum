<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function nullifyAssigneeByUserAndWorkspace(User $user, Workspace $workspace): void
    {
        $workspaceId = $workspace->getId();
        $userId = $user->getId();

        if ($workspaceId === null || $userId === null) {
            return;
        }

        $conn = $this->getEntityManager()->getConnection();

        $projectIds = $conn->fetchFirstColumn(
            'SELECT id FROM project WHERE workspace_id = :workspaceId',
            ['workspaceId' => $workspaceId->toBinary()],
        );

        if ($projectIds === []) {
            return;
        }

        $conn->executeStatement(
            'UPDATE task SET assignee_id = NULL WHERE assignee_id = :userId AND project_id IN (:projectIds)',
            ['userId' => $userId->toBinary(), 'projectIds' => $projectIds],
            ['projectIds' => ArrayParameterType::BINARY],
        );
    }

    /** @return Task[] */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.assignee', 'a')
            ->addSelect('a')
            ->where('IDENTITY(t.project) = :projectId')
            ->setParameter('projectId', $project->getId(), UuidType::NAME)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
