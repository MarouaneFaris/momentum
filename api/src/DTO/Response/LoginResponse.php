<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '018f5c2e-1234-7abc-8def-abcdef012345'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'name', type: 'string', example: 'Jane Doe'),
    ],
    type: 'object'
)]
final readonly class LoginResponse
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
        );
    }
}
