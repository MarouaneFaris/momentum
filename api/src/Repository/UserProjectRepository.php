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

    /**
     * @param Project[] $projects
     *
     * @return array<string, list<string>> project uuid string -> ordered list of member names
     */
    public function getMemberNamesForProjects(array $projects): array
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
            'SELECT up.project_id, u.name
             FROM user_project up
             INNER JOIN user u ON u.id = up.user_id
             WHERE up.project_id IN (:projectIds)
             ORDER BY up.assigned_at ASC',
            ['projectIds' => $projectIds],
            ['projectIds' => ArrayParameterType::BINARY],
        );

        $result = [];
        foreach ($rows as $row) {
            $uuid = $binaryToUuid[$row['project_id']] ?? null;
            if ($uuid === null) {
                continue;
            }
            $result[$uuid][] = $row['name'];
        }

        return $result;
    }

    public function deleteByUserAndWorkspace(User $user, Workspace $workspace): void
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
            'DELETE FROM user_project WHERE user_id = :userId AND project_id IN (:projectIds)',
            ['userId' => $userId->toBinary(), 'projectIds' => $projectIds],
            ['projectIds' => ArrayParameterType::BINARY],
        );
    }
}
