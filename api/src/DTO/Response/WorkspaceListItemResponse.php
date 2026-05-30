<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Workspace;
use App\Enum\WorkspaceRole;

final readonly class WorkspaceListItemResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $createdAt,
        public string $role,
    ) {}

    public static function fromWorkspaceAndRole(Workspace $workspace, WorkspaceRole $role): self
    {
        return new self(
            id: (string) $workspace->getId(),
            name: $workspace->getName(),
            createdAt: $workspace->getCreatedAt()->format(\DateTimeInterface::ATOM),
            role: $role->value,
        );
    }
}
