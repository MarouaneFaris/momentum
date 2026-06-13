<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\UpdateTaskDTO;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Error\ErrorCode;
use App\Event\TaskAssigned;
use App\Event\TaskStatusChanged;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Repository\UserWorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class TaskService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserWorkspaceRepository $userWorkspaceRepository,
        private EventDispatcherInterface $eventDispatcher,
        private UserRepository $userRepository,
    ) {}

    public function create(
        Project $project,
        User $creator,
        string $title,
        ?string $description,
        ?string $assigneeId,
    ): Task {
        $assignee = null;
        if ($assigneeId !== null) {
            $assignee = $this->userRepository->find($assigneeId);
            if ($assignee === null) {
                throw new ApiException(ErrorCode::VALIDATION_FAILED, 'Assignee not found.', ['field' => 'assigneeId']);
            }
            $this->validateAssignee($project, $assignee);
        }

        $task = new Task();
        $task->setProject($project);
        $task->setCreator($creator);
        $task->setTitle($title);
        $task->setDescription($description === '' ? null : $description);
        $task->setAssignee($assignee);

        $this->em->persist($task);
        $this->em->flush();

        if ($assignee !== null) {
            $this->eventDispatcher->dispatch(new TaskAssigned($task, $creator, $assignee));
        }

        return $task;
    }

    public function update(
        Task $task,
        User $caller,
        Workspace $workspace,
        UpdateTaskDTO $dto,
    ): Task {
        $membership = $this->userWorkspaceRepository->findOneBy([
            'user' => $caller,
            'workspace' => $workspace,
        ]);

        if ($membership === null) {
            throw new ApiException(ErrorCode::WORKSPACE_FORBIDDEN, 'Access denied.', [], Response::HTTP_FORBIDDEN);
        }

        $isOwner = $membership->getRole() === WorkspaceRole::Owner;
        $creatorId = $task->getCreator()->getId();
        $callerId = $caller->getId();
        $isCreator = $task->getCreator() === $caller
            || ($creatorId !== null && $callerId !== null && $creatorId->equals($callerId));
        $hasFullAccess = $isOwner || $isCreator;

        if (!$hasFullAccess && ($dto->title !== null || $dto->description !== null || $dto->assigneeId !== null || $dto->removeAssignee)) {
            throw new ApiException(ErrorCode::WORKSPACE_FORBIDDEN, 'Only status updates are allowed for this role.', [], Response::HTTP_FORBIDDEN);
        }

        $oldStatus = $task->getStatus();
        $oldAssignee = $task->getAssignee();

        if ($dto->title !== null) {
            $task->setTitle($dto->title);
        }

        if ($dto->description !== null) {
            $task->setDescription($dto->description === '' ? null : $dto->description);
        }

        if ($dto->status !== null) {
            $task->setStatus(TaskStatus::from($dto->status));
        }

        if ($hasFullAccess) {
            if ($dto->removeAssignee) {
                $task->setAssignee(null);
            } elseif ($dto->assigneeId !== null) {
                $newAssignee = $this->userRepository->find($dto->assigneeId);
                if ($newAssignee === null) {
                    throw new ApiException(ErrorCode::VALIDATION_FAILED, 'Assignee not found.', ['field' => 'assigneeId']);
                }
                $this->validateAssignee($task->getProject(), $newAssignee);
                $task->setAssignee($newAssignee);
            }
        }

        $this->em->flush();

        $newStatus = $task->getStatus();
        if ($dto->status !== null && $oldStatus !== $newStatus) {
            $this->eventDispatcher->dispatch(new TaskStatusChanged($task, $caller, $oldStatus, $newStatus));
        }

        $currentAssignee = $task->getAssignee();
        if ($hasFullAccess && $dto->assigneeId !== null && $currentAssignee !== null && $currentAssignee !== $oldAssignee) {
            $this->eventDispatcher->dispatch(new TaskAssigned($task, $task->getCreator(), $currentAssignee));
        }

        return $task;
    }

    public function delete(Task $task): void
    {
        $this->em->remove($task);
        $this->em->flush();
    }

    private function validateAssignee(Project $project, User $assignee): void
    {
        $membership = $this->userWorkspaceRepository->findOneBy([
            'user' => $assignee,
            'workspace' => $project->getWorkspace(),
        ]);

        if ($membership === null) {
            throw new ApiException(ErrorCode::VALIDATION_FAILED, 'Assignee is not a workspace member.', ['field' => 'assigneeId']);
        }

        if ($membership->getRole() === WorkspaceRole::Guest) {
            throw new ApiException(ErrorCode::VALIDATION_FAILED, 'Guests cannot be assigned tasks.', ['field' => 'assigneeId']);
        }
    }
}
