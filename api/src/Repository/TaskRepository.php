<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\TaskStatus;
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

    /** @return array{open: int, in_progress: int, done_this_week: int} */
    public function getStatsByWorkspaceAndUser(Workspace $workspace, User $user): array
    {
        $workspaceId = $workspace->getId();
        $userId = $user->getId();

        if ($workspaceId === null || $userId === null) {
            return ['open' => 0, 'in_progress' => 0, 'done_this_week' => 0];
        }

        $conn = $this->getEntityManager()->getConnection();

        $weekStart = (new \DateTimeImmutable())->setTime(0, 0)->modify('Monday this week');

        $row = $conn->fetchAssociative(
            'SELECT
                SUM(t.status = :todo) AS open,
                SUM(t.status = :in_progress) AS in_progress,
                SUM(t.status = :done AND t.updated_at >= :weekStart) AS done_this_week
            FROM task t
            INNER JOIN project p ON p.id = t.project_id
            WHERE p.workspace_id = :workspaceId
              AND t.assignee_id = :userId',
            [
                'todo' => TaskStatus::Todo->value,
                'in_progress' => TaskStatus::InProgress->value,
                'done' => TaskStatus::Done->value,
                'weekStart' => $weekStart->format('Y-m-d H:i:s'),
                'workspaceId' => $workspaceId->toBinary(),
                'userId' => $userId->toBinary(),
            ],
        );

        return [
            'open' => (int) ($row['open'] ?? 0),
            'in_progress' => (int) ($row['in_progress'] ?? 0),
            'done_this_week' => (int) ($row['done_this_week'] ?? 0),
        ];
    }

    /**
     * @param Project[] $projects
     *
     * @return array<string, array{total: int, done: int, open: int}> keyed by project uuid string
     */
    public function getStatsForProjects(array $projects): array
    {
        if ($projects === []) {
            return [];
        }

        $binaryToUuid = [];
        $projectIds = [];
        foreach ($projects as $p) {
            $id = $p->getId();
            if ($id !== null) {
                $binary = $id->toBinary();
                $projectIds[] = $binary;
                $binaryToUuid[$binary] = (string) $id;
            }
        }

        if ($projectIds === []) {
            return [];
        }

        $conn = $this->getEntityManager()->getConnection();

        $rows = $conn->fetchAllAssociative(
            'SELECT project_id, COUNT(*) AS total, SUM(status = :done) AS done
             FROM task
             WHERE project_id IN (:projectIds)
             GROUP BY project_id',
            ['done' => TaskStatus::Done->value, 'projectIds' => $projectIds],
            ['projectIds' => ArrayParameterType::BINARY],
        );

        $result = [];
        foreach ($rows as $row) {
            $uuid = $binaryToUuid[$row['project_id']] ?? null;
            if ($uuid === null) {
                continue;
            }
            $total = (int) $row['total'];
            $done = (int) $row['done'];
            $result[$uuid] = ['total' => $total, 'done' => $done, 'open' => $total - $done];
        }

        return $result;
    }

    /** @return Task[] */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.assignee', 'a')
            ->addSelect('a')
            ->leftJoin('t.creator', 'c')
            ->addSelect('c')
            ->where('IDENTITY(t.project) = :projectId')
            ->setParameter('projectId', $project->getId(), UuidType::NAME)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Task[] */
    public function findByWorkspaceAndUser(Workspace $workspace, User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.assignee', 'a')
            ->addSelect('a')
            ->leftJoin('t.creator', 'c')
            ->addSelect('c')
            ->join('t.project', 'p')
            ->where('IDENTITY(p.workspace) = :workspaceId')
            ->andWhere('IDENTITY(t.assignee) = :userId')
            ->setParameter('workspaceId', $workspace->getId(), UuidType::NAME)
            ->setParameter('userId', $user->getId(), UuidType::NAME)
            ->orderBy('t.updatedAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
