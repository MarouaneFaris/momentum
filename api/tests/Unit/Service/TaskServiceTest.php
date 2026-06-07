<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\UpdateTaskDTO;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\TaskStatus;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class TaskServiceTest extends TestCase
{
    private TaskService $service;
    private EntityManagerInterface&MockObject $em;
    private UserWorkspaceRepository&Stub $workspaceRepo;
    private Project $project;
    private User $creator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->workspaceRepo = $this->createStub(UserWorkspaceRepository::class);
        $this->service = new TaskService($this->em, $this->workspaceRepo);

        $workspace = new Workspace();
        $this->project = new Project();
        $this->project->setWorkspace($workspace);
        $this->creator = new User();
    }

    public function testCreatePersistsAndFlushes(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Task::class));
        $this->em->expects($this->once())->method('flush');

        $task = $this->service->create($this->project, $this->creator, 'My task', null, null);

        self::assertSame('My task', $task->getTitle());
        self::assertNull($task->getDescription());
        self::assertNull($task->getAssignee());
        self::assertSame($this->creator, $task->getCreator());
        self::assertSame($this->project, $task->getProject());
    }

    public function testCreateWithDescriptionAndNoAssignee(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $task = $this->service->create($this->project, $this->creator, 'Task', 'Some description', null);

        self::assertSame('Some description', $task->getDescription());
    }

    public function testCreateWithValidAssignee(): void
    {
        $assignee = new User();
        $membership = $this->makeMembership(WorkspaceRole::Member, $assignee);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $task = $this->service->create($this->project, $this->creator, 'Task', null, $assignee);

        self::assertSame($assignee, $task->getAssignee());
    }

    public function testCreateWithOwnerAssignee(): void
    {
        $assignee = new User();
        $membership = $this->makeMembership(WorkspaceRole::Owner, $assignee);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $task = $this->service->create($this->project, $this->creator, 'Task', null, $assignee);

        self::assertSame($assignee, $task->getAssignee());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCreateThrowsWhenAssigneeNotWorkspaceMember(): void
    {
        $assignee = new User();
        $this->workspaceRepo->method('findOneBy')->willReturn(null);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Assignee is not a workspace member');

        $this->service->create($this->project, $this->creator, 'Task', null, $assignee);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCreateThrowsWhenAssigneeIsGuest(): void
    {
        $assignee = new User();
        $membership = $this->makeMembership(WorkspaceRole::Guest, $assignee);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Guests cannot be assigned tasks');

        $this->service->create($this->project, $this->creator, 'Task', null, $assignee);
    }

    // update() tests

    public function testOwnerCanUpdateAllFields(): void
    {
        $owner = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Owner, $owner);
        $dto = new UpdateTaskDTO(title: 'New title', description: 'New desc', status: 'in-progress');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $owner, $this->project->getWorkspace(), $dto, null);

        self::assertSame('New title', $updated->getTitle());
        self::assertSame('New desc', $updated->getDescription());
        self::assertSame(TaskStatus::InProgress, $updated->getStatus());
    }

    public function testCreatorCanUpdateAllFields(): void
    {
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $this->creator);
        $dto = new UpdateTaskDTO(title: 'Creator update', status: 'done');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $this->creator, $this->project->getWorkspace(), $dto, null);

        self::assertSame('Creator update', $updated->getTitle());
        self::assertSame(TaskStatus::Done, $updated->getStatus());
    }

    public function testAssigneeNonCreatorCanUpdateStatusOnly(): void
    {
        $assignee = new User();
        $task = $this->makeTask($this->creator);
        $task->setAssignee($assignee);
        $membership = $this->makeMembership(WorkspaceRole::Member, $assignee);
        $dto = new UpdateTaskDTO(status: 'in-progress');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $assignee, $this->project->getWorkspace(), $dto, null);

        self::assertSame(TaskStatus::InProgress, $updated->getStatus());
    }

    public function testMemberNonCreatorNonAssigneeCanUpdateStatusOnly(): void
    {
        $member = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $member);
        $dto = new UpdateTaskDTO(status: 'done');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $member, $this->project->getWorkspace(), $dto, null);

        self::assertSame(TaskStatus::Done, $updated->getStatus());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMemberNonCreatorCannotUpdateTitle(): void
    {
        $member = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $member);
        $dto = new UpdateTaskDTO(title: 'Forbidden');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(AccessDeniedException::class);

        $this->service->update($task, $member, $this->project->getWorkspace(), $dto, null);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMemberNonCreatorCannotUpdateDescription(): void
    {
        $member = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $member);
        $dto = new UpdateTaskDTO(description: 'Forbidden');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(AccessDeniedException::class);

        $this->service->update($task, $member, $this->project->getWorkspace(), $dto, null);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMemberNonCreatorCannotUpdateAssignee(): void
    {
        $member = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $member);
        $dto = new UpdateTaskDTO(assigneeId: 'some-uuid');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(AccessDeniedException::class);

        $this->service->update($task, $member, $this->project->getWorkspace(), $dto, null);
    }

    public function testOwnerCanUpdateAssignee(): void
    {
        $owner = new User();
        $newAssignee = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Owner, $owner);
        $assigneeMembership = $this->makeMembership(WorkspaceRole::Member, $newAssignee);
        $this->workspaceRepo->method('findOneBy')->willReturnOnConsecutiveCalls($membership, $assigneeMembership);

        $dto = new UpdateTaskDTO(assigneeId: 'some-uuid');

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $owner, $this->project->getWorkspace(), $dto, $newAssignee);

        self::assertSame($newAssignee, $updated->getAssignee());
    }

    public function testOwnerCanRemoveAssignee(): void
    {
        $owner = new User();
        $task = $this->makeTask($this->creator);
        $task->setAssignee(new User());
        $membership = $this->makeMembership(WorkspaceRole::Owner, $owner);
        $dto = new UpdateTaskDTO(removeAssignee: true);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $owner, $this->project->getWorkspace(), $dto, null);

        self::assertNull($updated->getAssignee());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testMemberNonCreatorCannotRemoveAssignee(): void
    {
        $member = new User();
        $task = $this->makeTask($this->creator);
        $membership = $this->makeMembership(WorkspaceRole::Member, $member);
        $dto = new UpdateTaskDTO(removeAssignee: true);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(AccessDeniedException::class);

        $this->service->update($task, $member, $this->project->getWorkspace(), $dto, null);
    }

    public function testDescriptionEmptyStringClearsIt(): void
    {
        $task = $this->makeTask($this->creator);
        $task->setDescription('Existing desc');
        $membership = $this->makeMembership(WorkspaceRole::Member, $this->creator);
        $dto = new UpdateTaskDTO(description: '');
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $this->creator, $this->project->getWorkspace(), $dto, null);

        self::assertNull($updated->getDescription());
    }

    public function testNullFieldsAreNotUpdated(): void
    {
        $task = $this->makeTask($this->creator);
        $task->setTitle('Original');
        $task->setStatus(TaskStatus::Todo);
        $membership = $this->makeMembership(WorkspaceRole::Member, $this->creator);
        $dto = new UpdateTaskDTO();
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update($task, $this->creator, $this->project->getWorkspace(), $dto, null);

        self::assertSame('Original', $updated->getTitle());
        self::assertSame(TaskStatus::Todo, $updated->getStatus());
    }

    // delete() tests

    public function testDeleteRemovesAndFlushes(): void
    {
        $task = $this->makeTask($this->creator);

        $this->em->expects($this->once())->method('remove')->with($task);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($task);
    }

    private function makeTask(User $creator): Task
    {
        $task = new Task();
        $task->setProject($this->project);
        $task->setCreator($creator);
        $task->setTitle('Task');

        return $task;
    }

    private function makeMembership(WorkspaceRole $role, User $user): UserWorkspace
    {
        $m = new UserWorkspace();
        $m->setUser($user);
        $m->setWorkspace($this->project->getWorkspace());
        $m->setRole($role);

        return $m;
    }
}
