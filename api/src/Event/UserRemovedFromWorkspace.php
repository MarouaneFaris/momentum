<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use App\Entity\Workspace;

final readonly class UserRemovedFromWorkspace
{
    public function __construct(
        public User $removedUser,
        public Workspace $workspace,
    ) {}
}
