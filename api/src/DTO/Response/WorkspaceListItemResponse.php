<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use OpenApi\Attributes as OA;

final readonly class WorkspaceListItemResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012345')]
        public string $id,
        #[OA\Property(type: 'string', example: 'Acme Corp')]
        public string $name,
        #[OA\Property(type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00')]
        public string $createdAt,
        #[OA\Property(type: 'string', enum: ['owner', 'member', 'guest'], example: 'owner')]
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
