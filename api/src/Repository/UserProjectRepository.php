<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
