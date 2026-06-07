<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Task;
use App\Entity\User;

final readonly class TaskAssigned
{
    public function __construct(
        public Task $task,
        public User $creator,
        public User $assignee,
    ) {}
}
