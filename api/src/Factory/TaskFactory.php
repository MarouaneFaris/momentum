<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Task;
use App\Enum\TaskStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Task>
 */
final class TaskFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Task::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'title' => self::faker()->sentence(4),
            'description' => null,
            'status' => TaskStatus::Todo,
            'project' => ProjectFactory::new(),
            'creator' => UserFactory::new(),
            'assignee' => null,
        ];
    }
}
