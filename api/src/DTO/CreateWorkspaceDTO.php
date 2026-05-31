<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkspaceDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        #[OA\Property(type: 'string', maxLength: 64, example: 'Acme Corp')]
        public string $name,
    ) {}
}
