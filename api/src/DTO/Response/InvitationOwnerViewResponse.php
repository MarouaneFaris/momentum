<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\WorkspaceInvitation;
use OpenApi\Attributes as OA;

final readonly class InvitationOwnerViewResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        /** @var array{id: string, name: string, email: string} */
        #[OA\Property(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
        ])]
        public array $invitee,
        #[OA\Property(type: 'string', enum: ['member', 'guest'])]
        public string $role,
        #[OA\Property(type: 'string', enum: ['pending', 'expired'])]
        public string $status,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $expiresAt,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $createdAt,
    ) {}

    public static function fromInvitation(WorkspaceInvitation $invitation, \DateTimeImmutable $now): self
    {
        $invitee = $invitation->getInvitee();

        return new self(
            id: (string) $invitation->getId(),
            invitee: [
                'id' => (string) $invitee->getId(),
                'name' => $invitee->getName(),
                'email' => $invitee->getEmail(),
            ],
            role: $invitation->getRole()->value,
            status: $invitation->getExpiresAt() > $now ? 'pending' : 'expired',
            expiresAt: $invitation->getExpiresAt()->format(\DateTimeInterface::ATOM),
            createdAt: $invitation->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
