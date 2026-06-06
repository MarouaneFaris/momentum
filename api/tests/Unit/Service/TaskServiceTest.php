<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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

    public function testCreateThrowsWhenAssigneeNotWorkspaceMember(): void
    {
        $assignee = new User();
        $this->workspaceRepo->method('findOneBy')->willReturn(null);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Assignee is not a workspace member');

        $this->service->create($this->project, $this->creator, 'Task', null, $assignee);
    }

    public function testCreateThrowsWhenAssigneeIsGuest(): void
    {
        $assignee = new User();
        $membership = $this->makeMembership(WorkspaceRole::Guest, $assignee);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Guests cannot be assigned tasks');

        $this->service->create($this->project, $this->creator, 'Task', null, $assignee);
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
