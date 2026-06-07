<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;

final readonly class TaskStatusChanged
{
    public function __construct(
        public Task $task,
        public User $actor,
        public TaskStatus $oldStatus,
        public TaskStatus $newStatus,
    ) {}
}
