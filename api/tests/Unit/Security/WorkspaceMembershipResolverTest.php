<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\WorkspaceMembership;
use App\Security\WorkspaceMembershipResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspaceMembershipResolverTest extends TestCase
{
    private WorkspaceMembershipResolver $resolver;
    private UserWorkspaceRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserWorkspaceRepository::class);
        $this->resolver = new WorkspaceMembershipResolver($this->repository);
    }

    public function testReturnsNullWhenNotMember(): void
    {
        [$user, $workspace] = $this->makeUserAndWorkspace();
        $this->repository->method('findOneBy')->willReturn(null);

        self::assertNull($this->resolver->for($user, $workspace));
    }

    public function testReturnsMembershipWithCorrectRole(): void
    {
        [$user, $workspace] = $this->makeUserAndWorkspace();
        $uw = $this->makeUserWorkspace(WorkspaceRole::Member);
        $this->repository->method('findOneBy')->willReturn($uw);

        $membership = $this->resolver->for($user, $workspace);

        self::assertInstanceOf(WorkspaceMembership::class, $membership);
        self::assertSame(WorkspaceRole::Member, $membership->role);
    }

    public function testMemoizesPerRequest(): void
    {
        [$user, $workspace] = $this->makeUserAndWorkspace();
        $uw = $this->makeUserWorkspace(WorkspaceRole::Owner);

        // Repository must be called exactly once even though we call resolver twice
        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($uw);

        $first = $this->resolver->for($user, $workspace);
        $second = $this->resolver->for($user, $workspace);

        self::assertSame($first, $second);
    }

    public function testMemoizesNullResult(): void
    {
        [$user, $workspace] = $this->makeUserAndWorkspace();

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $first = $this->resolver->for($user, $workspace);
        $second = $this->resolver->for($user, $workspace);

        self::assertNull($first);
        self::assertNull($second);
    }

    public function testDifferentWorkspacesHitRepositorySeparately(): void
    {
        [$user, $workspace1] = $this->makeUserAndWorkspace();
        [, $workspace2] = $this->makeUserAndWorkspace();

        $this->repository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $this->resolver->for($user, $workspace1);
        $this->resolver->for($user, $workspace2);
    }

    public function testReturnsNullWhenUserHasNoId(): void
    {
        $user = new User(); // no ID set
        $workspace = $this->createStub(Workspace::class);
        $workspace->method('getId')->willReturn(Uuid::v7());

        $this->repository->expects(self::never())->method('findOneBy');

        self::assertNull($this->resolver->for($user, $workspace));
    }

    /** @return array{User&Stub, Workspace&Stub} */
    private function makeUserAndWorkspace(): array
    {
        $user = $this->createStub(User::class);
        $user->method('getId')->willReturn(Uuid::v7());

        $workspace = $this->createStub(Workspace::class);
        $workspace->method('getId')->willReturn(Uuid::v7());

        return [$user, $workspace];
    }

    private function makeUserWorkspace(WorkspaceRole $role): UserWorkspace&Stub
    {
        $uw = $this->createStub(UserWorkspace::class);
        $uw->method('getRole')->willReturn($role);

        return $uw;
    }
}
