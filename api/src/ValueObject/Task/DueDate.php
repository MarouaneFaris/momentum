<?php

declare(strict_types=1);

namespace App\ValueObject\Task;

use Doctrine\ORM\Mapping\Embeddable;
use Symfony\Component\Clock\ClockInterface;

#[Embeddable]
final readonly class DueDate
{
    public function __construct(private \DateTimeImmutable $datetime) {}

    public function isOverdue(ClockInterface $clock): bool
    {
        return $this->datetime < $clock->now();
    }

    public function toDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->datetime;
    }
}
