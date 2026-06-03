<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Project;
use App\Enum\ProjectStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Project>
 */
final class ProjectFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Project::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => null,
            'status' => ProjectStatus::Active,
            'logoUrl' => null,
            'workspace' => WorkspaceFactory::new(),
            'owner' => UserWorkspaceFactory::new(),
        ];
    }
}
