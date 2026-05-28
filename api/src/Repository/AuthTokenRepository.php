<?php

namespace App\Repository;

use App\Entity\AuthToken;
use App\Entity\Workspace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;

/**
 * @extends ServiceEntityRepository<AuthToken>
 */
class AuthTokenRepository extends ServiceEntityRepository implements WorkspaceScopedRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthToken::class);
    }

    public function findByWorkspace(Workspace $workspace): array
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT at FROM App\Entity\AuthToken at
                 JOIN App\Entity\UserWorkspace uw WITH uw.user = at.user
                 WHERE IDENTITY(uw.workspace) = :workspaceId'
            )
            ->setParameter('workspaceId', $workspace->getId(), UuidType::NAME)
            ->getResult();
    }
}
