<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class TaskService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    public function create(
        Project $project,
        User $creator,
        string $title,
        ?string $description,
        ?User $assignee,
    ): Task {
        if ($assignee !== null) {
            $this->validateAssignee($project, $assignee);
        }

        $task = new Task();
        $task->setProject($project);
        $task->setCreator($creator);
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setAssignee($assignee);

        $this->em->persist($task);
        $this->em->flush();

        return $task;
    }

    private function validateAssignee(Project $project, User $assignee): void
    {
        $membership = $this->userWorkspaceRepository->findOneBy([
            'user' => $assignee,
            'workspace' => $project->getWorkspace(),
        ]);

        if ($membership === null) {
            throw new UnprocessableEntityHttpException('Assignee is not a workspace member');
        }

        if ($membership->getRole() === WorkspaceRole::Guest) {
            throw new UnprocessableEntityHttpException('Guests cannot be assigned tasks');
        }
    }
}
