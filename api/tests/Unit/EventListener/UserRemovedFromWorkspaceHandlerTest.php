<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\Entity\Workspace;
use App\Event\UserRemovedFromWorkspace;
use App\EventListener\UserRemovedFromWorkspaceHandler;
use App\Repository\UserProjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserRemovedFromWorkspaceHandlerTest extends TestCase
{
    private UserProjectRepository&MockObject $userProjectRepository;
    private UserRemovedFromWorkspaceHandler $handler;

    protected function setUp(): void
    {
        $this->userProjectRepository = $this->createMock(UserProjectRepository::class);
        $this->handler = new UserRemovedFromWorkspaceHandler($this->userProjectRepository);
    }

    public function testDeletesUserProjectRowsForRemovedUser(): void
    {
        $user = new User();
        $workspace = new Workspace();

        $this->userProjectRepository
            ->expects($this->once())
            ->method('deleteByUserAndWorkspace')
            ->with($user, $workspace);

        ($this->handler)(new UserRemovedFromWorkspace($user, $workspace));
    }

    public function testNoOpWhenUserHasNoProjectAssignments(): void
    {
        $user = new User();
        $workspace = new Workspace();

        $this->userProjectRepository
            ->expects($this->once())
            ->method('deleteByUserAndWorkspace')
            ->with($user, $workspace);

        ($this->handler)(new UserRemovedFromWorkspace($user, $workspace));
    }
}
