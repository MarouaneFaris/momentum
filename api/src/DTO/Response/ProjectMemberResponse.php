<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\UserProject;
use OpenApi\Attributes as OA;

final readonly class ProjectMemberResponse
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string')]
        public string $name,
        #[OA\Property(type: 'string', format: 'email')]
        public string $email,
        #[OA\Property(type: 'string', format: 'date-time')]
        public string $assignedAt,
    ) {}

    public static function fromUserProject(UserProject $up): self
    {
        $user = $up->getUser();

        return new self(
            id: (string) $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            assignedAt: $up->getAssignedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
