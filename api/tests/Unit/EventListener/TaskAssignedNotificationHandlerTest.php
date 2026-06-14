<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Event\TaskAssigned;
use App\EventListener\TaskAssignedNotificationHandler;
use App\Notification\NotificationOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskAssignedNotificationHandlerTest extends TestCase
{
    private NotificationOrchestrator&MockObject $orchestrator;
    private TaskAssignedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->orchestrator = $this->createMock(NotificationOrchestrator::class);
        $this->handler = new TaskAssignedNotificationHandler($this->orchestrator);
    }

    public function testDelegatesToOrchestrator(): void
    {
        $creator = new User();
        $assignee = new User();
        $task = $this->makeTask($creator, $assignee);

        $this->orchestrator
            ->expects($this->once())
            ->method('taskAssigned')
            ->with($task, $creator, $assignee);

        ($this->handler)(new TaskAssigned($task, $creator, $assignee));
    }

    private function makeTask(User $creator, ?User $assignee): Task
    {
        $workspace = new Workspace();
        $project = new Project();
        $project->setWorkspace($workspace);
        $project->setName('Test Project');

        $task = new Task();
        $task->setCreator($creator);
        $task->setAssignee($assignee);
        $task->setTitle('Do something');
        $task->setProject($project);

        return $task;
    }
}
