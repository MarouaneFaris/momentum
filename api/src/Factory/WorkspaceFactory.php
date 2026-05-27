<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Workspace;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Workspace>
 */
final class WorkspaceFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Workspace::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'creator' => UserFactory::new(),
        ];
    }
}
