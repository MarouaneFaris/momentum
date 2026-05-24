<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\User;

final readonly class LoginResponse
{
    public function __construct(
        public string $id,
        public string $email,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->getId(),
            email: $user->getEmail(),
        );
    }
}
