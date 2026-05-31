<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\WorkspaceInvitation;
use OpenApi\Attributes as OA;

final readonly class InvitationInviteeViewResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        /** @var array{id: string, name: string} */
        #[OA\Property(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
        ])]
        public array $workspace,
        /** @var array{id: string, name: string}|null */
        #[OA\Property(type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
        ])]
        public ?array $invitedBy,
        #[OA\Property(type: 'string', enum: ['member', 'guest'])]
        public string $role,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $expiresAt,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $createdAt,
    ) {}

    public static function fromInvitation(WorkspaceInvitation $invitation): self
    {
        $invitedBy = $invitation->getInvitedBy();

        return new self(
            id: (string) $invitation->getId(),
            workspace: [
                'id' => (string) $invitation->getWorkspace()->getId(),
                'name' => $invitation->getWorkspace()->getName(),
            ],
            invitedBy: $invitedBy !== null ? [
                'id' => (string) $invitedBy->getId(),
                'name' => $invitedBy->getName(),
            ] : null,
            role: $invitation->getRole()->value,
            expiresAt: $invitation->getExpiresAt()->format(\DateTimeInterface::ATOM),
            createdAt: $invitation->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
