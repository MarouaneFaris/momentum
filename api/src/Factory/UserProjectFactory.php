<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\UserProject;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UserProject>
 */
final class UserProjectFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return UserProject::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'project' => ProjectFactory::new(),
            'user' => UserFactory::new(),
        ];
    }
}
