<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\User;
use App\Entity\WorkspaceInvitation;

final readonly class WorkspaceInvitationCancelled
{
    public function __construct(
        public WorkspaceInvitation $invitation,
        public User $actor,
    ) {}
}
