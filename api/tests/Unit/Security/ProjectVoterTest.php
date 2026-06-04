<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\ProjectVoter;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

final class ProjectVoterTest extends TestCase
{
    private ProjectVoter $voter;
    private UserWorkspaceRepository&Stub $repository;
    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        $this->repository = $this->createStub(UserWorkspaceRepository::class);
        $this->voter = new ProjectVoter($this->repository);
        $this->user = new User();
        $this->workspace = new Workspace();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideViewCombinations')]
    public function testViewAttribute(WorkspaceRole $role, int $expectedVote): void
    {
        $this->repository
            ->method('findRoleByUserAndWorkspace')
            ->willReturn($role);

        $result = $this->voter->vote($this->createToken(), $this->workspace, [ProjectVoter::VIEW]);

        self::assertSame($expectedVote, $result);
    }

    /** @return iterable<string, array{WorkspaceRole, int}> */
    public static function provideViewCombinations(): iterable
    {
        yield 'owner can view' => [WorkspaceRole::Owner, VoterInterface::ACCESS_GRANTED];
        yield 'member can view' => [WorkspaceRole::Member, VoterInterface::ACCESS_GRANTED];
        yield 'guest can view' => [WorkspaceRole::Guest, VoterInterface::ACCESS_GRANTED];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideCreateCombinations')]
    public function testCreateAttribute(WorkspaceRole $role, int $expectedVote): void
    {
        $this->repository
            ->method('findRoleByUserAndWorkspace')
            ->willReturn($role);

        $result = $this->voter->vote($this->createToken(), $this->workspace, [ProjectVoter::CREATE]);

        self::assertSame($expectedVote, $result);
    }

    /** @return iterable<string, array{WorkspaceRole, int}> */
    public static function provideCreateCombinations(): iterable
    {
        yield 'owner can create' => [WorkspaceRole::Owner, VoterInterface::ACCESS_GRANTED];
        yield 'member can create' => [WorkspaceRole::Member, VoterInterface::ACCESS_GRANTED];
        yield 'guest denied create' => [WorkspaceRole::Guest, VoterInterface::ACCESS_DENIED];
    }

    public function testOwnerCanEditAnyProject(): void
    {
        $ownerMembership = $this->makeMembership(WorkspaceRole::Owner, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($ownerMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanEditOwnProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($memberMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotEditOtherMembersProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDeniedEdit(): void
    {
        $project = $this->makeProject($this->makeMembership(WorkspaceRole::Member, Uuid::v4()));

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanDeleteAnyProject(): void
    {
        $ownerMembership = $this->makeMembership(WorkspaceRole::Owner, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($ownerMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanDeleteOwnProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($memberMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotDeleteOtherMembersProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDeniedDelete(): void
    {
        $project = $this->makeProject($this->makeMembership(WorkspaceRole::Member, Uuid::v4()));

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanManageMembersOnAnyProject(): void
    {
        $ownerMembership = $this->makeMembership(WorkspaceRole::Owner, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($ownerMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanManageMembersOnOwnProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($memberMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotManageMembersOnOtherMembersProject(): void
    {
        $memberMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $otherMembership = $this->makeMembership(WorkspaceRole::Member, Uuid::v4());
        $project = $this->makeProject($otherMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($memberMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGuestIsDeniedManageMembers(): void
    {
        $guestMembership = $this->makeMembership(WorkspaceRole::Guest, Uuid::v4());
        $project = $this->makeProject($guestMembership);

        $this->repository
            ->method('findOneBy')
            ->willReturn($guestMembership);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDenied(): void
    {
        $this->repository
            ->method('findRoleByUserAndWorkspace')
            ->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->workspace, [ProjectVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsOnUnsupportedSubject(): void
    {
        $result = $this->voter->vote($this->createToken(), new \stdClass(), [ProjectVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $result = $this->voter->vote($this->createToken(), $this->workspace, ['unsupported.attribute']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    private function createToken(): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        return $token;
    }

    private function makeMembership(WorkspaceRole $role, Uuid $id): UserWorkspace&Stub
    {
        $membership = $this->createStub(UserWorkspace::class);
        $membership->method('getRole')->willReturn($role);
        $membership->method('getId')->willReturn($id);
        $membership->method('getWorkspace')->willReturn($this->workspace);

        return $membership;
    }

    private function makeProject(UserWorkspace $owner): Project&Stub
    {
        $project = $this->createStub(Project::class);
        $project->method('getOwner')->willReturn($owner);
        $project->method('getWorkspace')->willReturn($this->workspace);

        return $project;
    }
}
