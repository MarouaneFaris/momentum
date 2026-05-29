<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Workspace;
use App\Service\WorkspaceContext;
use PHPUnit\Framework\TestCase;

final class WorkspaceContextTest extends TestCase
{
    public function testGetReturnsNullByDefault(): void
    {
        $context = new WorkspaceContext();

        self::assertNull($context->get());
    }

    public function testSetAndGet(): void
    {
        $context = new WorkspaceContext();
        $workspace = $this->createStub(Workspace::class);

        $context->set($workspace);

        self::assertSame($workspace, $context->get());
    }

    public function testGetOrFailThrowsWhenNotInitialized(): void
    {
        $context = new WorkspaceContext();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('WorkspaceContext not initialized');

        $context->getOrFail();
    }

    public function testGetOrFailReturnsWorkspaceWhenSet(): void
    {
        $context = new WorkspaceContext();
        $workspace = $this->createStub(Workspace::class);

        $context->set($workspace);

        self::assertSame($workspace, $context->getOrFail());
    }

    public function testResetClearsWorkspace(): void
    {
        $context = new WorkspaceContext();
        $context->set($this->createStub(Workspace::class));

        $context->reset();

        self::assertNull($context->get());
    }
}
