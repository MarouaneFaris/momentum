<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\WorkspaceRole;
use Symfony\Component\Uid\Uuid;

final readonly class WorkspaceMembership
{
    public function __construct(
        public Uuid $userId,
        public Uuid $workspaceId,
        public WorkspaceRole $role,
    ) {}

    public function can(Capability $capability): bool
    {
        return \in_array($capability, CapabilityMap::for($this->role), true);
    }
}
