<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Project;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Event\ProjectOwnerRemoved;
use App\EventListener\ProjectOwnerRemovedHandler;
use App\Repository\ProjectRepository;
use App\Repository\UserWorkspaceRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ProjectOwnerRemovedHandlerTest extends TestCase
{
    private ProjectRepository&MockObject $projectRepository;
    private UserWorkspaceRepository&MockObject $userWorkspaceRepository;
    private ProjectOwnerRemovedHandler $handler;

    protected function setUp(): void
    {
        $this->projectRepository = $this->createMock(ProjectRepository::class);
        $this->userWorkspaceRepository = $this->createMock(UserWorkspaceRepository::class);
        $this->handler = new ProjectOwnerRemovedHandler(
            $this->projectRepository,
            $this->userWorkspaceRepository,
        );
    }

    public function testReassignsOwnedProjectsToWorkspaceOwner(): void
    {
        $workspace = new Workspace();
        $removedMembership = new UserWorkspace();
        $ownerMembership = new UserWorkspace();

        $project1 = new Project();
        $project1->setWorkspace($workspace);
        $project1->setOwner($removedMembership);
        $project1->setName('Project 1');

        $project2 = new Project();
        $project2->setWorkspace($workspace);
        $project2->setOwner($removedMembership);
        $project2->setName('Project 2');

        $this->projectRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['owner' => $removedMembership])
            ->willReturn([$project1, $project2]);

        $this->userWorkspaceRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['workspace' => $workspace, 'role' => WorkspaceRole::Owner])
            ->willReturn($ownerMembership);

        ($this->handler)(new ProjectOwnerRemoved($removedMembership, $workspace));

        self::assertSame($ownerMembership, $project1->getOwner());
        self::assertSame($ownerMembership, $project2->getOwner());
    }

    public function testNoOpWhenMemberOwnsNoProjects(): void
    {
        $workspace = new Workspace();
        $removedMembership = new UserWorkspace();

        $this->projectRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['owner' => $removedMembership])
            ->willReturn([]);

        $this->userWorkspaceRepository
            ->expects($this->never())
            ->method('findOneBy');

        ($this->handler)(new ProjectOwnerRemoved($removedMembership, $workspace));
    }
}
