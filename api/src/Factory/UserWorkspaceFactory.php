<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\UserWorkspace;
use App\Enum\WorkspaceRole;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UserWorkspace>
 */
final class UserWorkspaceFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return UserWorkspace::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'workspace' => WorkspaceFactory::new(),
            'role' => WorkspaceRole::Member,
        ];
    }
}
