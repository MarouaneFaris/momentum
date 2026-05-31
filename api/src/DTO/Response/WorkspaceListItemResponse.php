<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WorkspaceResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012345'),
        new OA\Property(property: 'name', type: 'string', example: 'Acme Corp'),
        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-01-01T00:00:00+00:00'),
        new OA\Property(property: 'role', type: 'string', enum: ['owner', 'member', 'guest'], example: 'owner'),
    ],
    type: 'object'
)]
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
