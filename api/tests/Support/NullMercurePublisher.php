<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\Mercure\Update;

final class NullMercurePublisher
{
    public function __invoke(Update $update): string
    {
        return '';
    }
}
