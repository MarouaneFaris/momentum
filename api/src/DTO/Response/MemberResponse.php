<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\UserWorkspace;
use OpenApi\Attributes as OA;

final readonly class MemberResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string')]
        public string $name,
        #[OA\Property(type: 'string', format: 'email')]
        public string $email,
        #[OA\Property(type: 'string', enum: ['owner', 'member', 'guest'])]
        public string $role,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $joinedAt,
    ) {}

    public static function fromUserWorkspace(UserWorkspace $uw): self
    {
        $user = $uw->getUser();

        return new self(
            id: (string) $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            role: $uw->getRole()->value,
            joinedAt: $uw->getJoinedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
