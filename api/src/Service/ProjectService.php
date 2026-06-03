<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function create(
        Workspace $workspace,
        UserWorkspace $owner,
        string $name,
        ?string $description,
        ProjectStatus $status,
    ): Project {
        $project = new Project();
        $project->setWorkspace($workspace);
        $project->setOwner($owner);
        $project->setName($name);
        $project->setDescription($description);
        $project->setStatus($status);

        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    public function delete(Project $project): void
    {
        // cascade stub: child tasks deleted here when Task entity exists
        $this->em->remove($project);
        $this->em->flush();
    }

    public function update(
        Project $project,
        ?string $name,
        ?string $description,
        ?ProjectStatus $status,
    ): void {
        if ($name !== null) {
            $project->setName($name);
        }

        if ($description !== null) {
            $project->setDescription($description === '' ? null : $description);
        }

        if ($status !== null) {
            $project->setStatus($status);
        }

        $this->em->flush();
    }
}
