<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class ProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
        #[Target('project_status')]
        private WorkflowInterface $workflow,
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

        if ($status !== null && $status !== $project->getStatus()) {
            $transition = null;
            foreach ($this->workflow->getEnabledTransitions($project) as $t) {
                if (\in_array($status->value, $t->getTos(), true)) {
                    $transition = $t->getName();
                    break;
                }
            }

            if ($transition === null) {
                throw new \LogicException(sprintf('Cannot transition project from "%s" to "%s".', $project->getStatus()->value, $status->value));
            }

            $this->workflow->apply($project, $transition);
        }

        $this->em->flush();
    }

    public function assignGuest(Project $project, User $user): UserProject
    {
        $assignment = new UserProject();
        $assignment->setProject($project);
        $assignment->setUser($user);

        $this->em->persist($assignment);
        $this->em->flush();

        return $assignment;
    }

    public function removeGuest(UserProject $assignment): void
    {
        $this->em->remove($assignment);
        $this->em->flush();
    }
}
