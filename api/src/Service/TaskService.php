<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\UpdateTaskDTO;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    public function update(
        Task $task,
        User $caller,
        UserWorkspace $membership,
        UpdateTaskDTO $dto,
        ?User $newAssignee,
    ): Task {
        $isOwner = $membership->getRole() === WorkspaceRole::Owner;
        $creatorId = $task->getCreator()->getId();
        $callerId = $caller->getId();
        $isCreator = $task->getCreator() === $caller
            || ($creatorId !== null && $callerId !== null && $creatorId->equals($callerId));
        $hasFullAccess = $isOwner || $isCreator;

        if (!$hasFullAccess && ($dto->title !== null || $dto->description !== null || $dto->assigneeId !== null)) {
            throw new AccessDeniedException('Only status updates are allowed for this role');
        }

        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $task->setDescription($dto->description === '' ? null : $dto->description);
        }

        if ($dto->status !== null) {
            $task->setStatus(TaskStatus::from($dto->status));
        }

        if ($dto->assigneeId !== null && $hasFullAccess) {
            if ($newAssignee !== null) {
                $this->validateAssignee($task->getProject(), $newAssignee);
            }
            $task->setAssignee($newAssignee);
        }

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
