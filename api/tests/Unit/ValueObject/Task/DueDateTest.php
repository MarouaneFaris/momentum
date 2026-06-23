<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject\Task;

use App\ValueObject\Task\DueDate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\ClockInterface;

final class DueDateTest extends TestCase
{
    public function testDueDateIsOverdue(): void
    {
        $clock = $this->getClockStub();
        $dueDate = new DueDate($clock->now()->sub(new \DateInterval('P1D')));
        static::assertTrue($dueDate->isOverdue($clock));
    }

    public function testDueDateIsNotOverdue(): void
    {
        $clock = $this->getClockStub();
        $dueDate = new DueDate($clock->now()->add(new \DateInterval('P1D')));
        static::assertFalse($dueDate->isOverdue($clock));
    }

    public function testDueDateIsNotOverdueNow(): void
    {
        $clock = $this->getClockStub();
        $dueDate = new DueDate($clock->now());
        static::assertFalse($dueDate->isOverdue($clock));
    }

    private function getClockStub(): ClockInterface
    {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2026-06-23 12:00:00'));

        return $clock;
    }
}
