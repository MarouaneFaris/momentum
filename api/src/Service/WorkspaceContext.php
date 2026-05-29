<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Workspace;

final class WorkspaceContext
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function get(): ?Workspace
    {
        return $this->workspace;
    }

    public function getOrFail(): Workspace
    {
        return $this->workspace ?? throw new \LogicException('WorkspaceContext not initialized for this request.');
    }
}
