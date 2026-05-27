<?php

namespace App\Repository;

use App\Entity\Workspace;

interface WorkspaceScopedRepositoryInterface
{
    /**
     * @return list<object>
     */
    public function findByWorkspace(Workspace $workspace): array;
}
