<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\TaskStatus;
use App\Event\TaskStatusChanged;
use App\EventListener\TaskStatusChangedNotificationHandler;
use App\Notification\NotificationOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TaskStatusChangedNotificationHandlerTest extends TestCase
{
    private NotificationOrchestrator&MockObject $orchestrator;
    private TaskStatusChangedNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->orchestrator = $this->createMock(NotificationOrchestrator::class);
        $this->handler = new TaskStatusChangedNotificationHandler($this->orchestrator);
    }

    public function testDelegatesToOrchestrator(): void
    {
        $actor = new User();
        $task = $this->makeTask(new User(), null);

        $this->orchestrator
            ->expects($this->once())
            ->method('taskStatusChanged')
            ->with($task, $actor, TaskStatus::InProgress);

        ($this->handler)(new TaskStatusChanged($task, $actor, TaskStatus::Todo, TaskStatus::InProgress));
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
        $task->setTitle('Some task');
        $task->setProject($project);

        return $task;
    }
}
