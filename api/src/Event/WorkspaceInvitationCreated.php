<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\WorkspaceInvitation;

final readonly class WorkspaceInvitationCreated
{
    public function __construct(
        public WorkspaceInvitation $invitation,
    ) {}
}
