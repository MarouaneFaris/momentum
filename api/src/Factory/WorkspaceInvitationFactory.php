<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\WorkspaceInvitation;
use App\Enum\WorkspaceRole;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<WorkspaceInvitation>
 */
final class WorkspaceInvitationFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return WorkspaceInvitation::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'workspace' => WorkspaceFactory::new(),
            'invitee' => UserFactory::new(),
            'invitedBy' => UserFactory::new(),
            'role' => WorkspaceRole::Member,
            'createdAt' => $now,
            'expiresAt' => $now->modify('+7 days'),
        ];
    }
}
