<?php

declare(strict_types=1);

namespace App\DTO\Response;

use App\Entity\User;

final readonly class LoginResponse
{
    public function __construct(
        public int $id,
        public string $email,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
        );
    }
}
