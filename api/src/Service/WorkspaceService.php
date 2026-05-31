<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use Doctrine\ORM\EntityManagerInterface;

final readonly class WorkspaceService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function create(string $name, User $creator): Workspace
    {
        $workspace = new Workspace();
        $workspace->setName($name);
        $workspace->setCreator($creator);
        $this->em->persist($workspace);

        $userWorkspace = new UserWorkspace();
        $userWorkspace->setUser($creator);
        $userWorkspace->setWorkspace($workspace);
        $userWorkspace->setRole(WorkspaceRole::Owner);
        $this->em->persist($userWorkspace);

        $this->em->flush();

        return $workspace;
    }

    public function rename(Workspace $workspace, string $name): void
    {
        $workspace->setName($name);
        $this->em->flush();
    }

    public function delete(Workspace $workspace): void
    {
        $this->em->remove($workspace);
        $this->em->flush();
    }
}
