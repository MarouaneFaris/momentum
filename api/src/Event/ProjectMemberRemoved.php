<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\UserWorkspace;
use App\Entity\Workspace;

final readonly class ProjectMemberRemoved
{
    public function __construct(
        public UserWorkspace $removedMembership,
        public Workspace $workspace,
    ) {}
}
