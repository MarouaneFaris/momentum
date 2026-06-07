<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Event\ProjectMemberRemoved;
use App\EventListener\TaskAssigneeNullifiedHandler;
use App\Repository\TaskRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskAssigneeNullifiedHandlerTest extends TestCase
{
    private TaskRepository&MockObject $taskRepository;
    private TaskAssigneeNullifiedHandler $handler;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->handler = new TaskAssigneeNullifiedHandler($this->taskRepository);
    }

    public function testNullifiesAssigneeOnTasksInWorkspace(): void
    {
        $user = new User();
        $workspace = new Workspace();
        $membership = new UserWorkspace();
        $membership->setUser($user);
        $membership->setWorkspace($workspace);

        $this->taskRepository
            ->expects($this->once())
            ->method('nullifyAssigneeByUserAndWorkspace')
            ->with($user, $workspace);

        ($this->handler)(new ProjectMemberRemoved($membership, $workspace));
    }

    public function testSkipsTasksNotAssignedToRemovedMember(): void
    {
        $user = new User();
        $workspace = new Workspace();
        $membership = new UserWorkspace();
        $membership->setUser($user);
        $membership->setWorkspace($workspace);

        $this->taskRepository
            ->expects($this->once())
            ->method('nullifyAssigneeByUserAndWorkspace')
            ->with($user, $workspace);

        ($this->handler)(new ProjectMemberRemoved($membership, $workspace));
    }
}
