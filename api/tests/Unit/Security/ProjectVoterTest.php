<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Voter\ProjectVoter;
use App\Security\WorkspaceMembership;
use App\Security\WorkspaceMembershipResolver;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

final class ProjectVoterTest extends TestCase
{
    private ProjectVoter $voter;
    private WorkspaceMembershipResolver&Stub $resolver;
    private User $user;
    private Workspace $workspace;
    private Uuid $userId;

    protected function setUp(): void
    {
        $this->resolver = $this->createStub(WorkspaceMembershipResolver::class);
        $this->voter = new ProjectVoter($this->resolver);
        $this->userId = Uuid::v7();
        $this->user = $this->createStub(User::class);
        $this->user->method('getId')->willReturn($this->userId);
        $this->workspace = new Workspace();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideViewCombinations')]
    public function testViewAttribute(WorkspaceRole $role, int $expectedVote): void
    {
        $this->resolver
            ->method('for')
            ->willReturn($this->makeMembership($role));

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
        $this->resolver
            ->method('for')
            ->willReturn($this->makeMembership($role));

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
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Owner));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanEditOwnProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: true);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotEditOtherMembersProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDeniedEdit(): void
    {
        $this->resolver->method('for')->willReturn(null);
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanDeleteAnyProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Owner));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanDeleteOwnProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: true);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotDeleteOtherMembersProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDeniedDelete(): void
    {
        $this->resolver->method('for')->willReturn(null);
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerCanManageMembersOnAnyProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Owner));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCanManageMembersOnOwnProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: true);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testMemberCannotManageMembersOnOtherMembersProject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $project = $this->makeProject(ownedByCurrentUser: false);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGuestIsDeniedManageMembers(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $project = $this->makeProject(ownedByCurrentUser: true);

        $result = $this->voter->vote($this->createToken(), $project, [ProjectVoter::MANAGE_MEMBERS]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberIsDenied(): void
    {
        $this->resolver->method('for')->willReturn(null);

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

    private function makeMembership(WorkspaceRole $role): WorkspaceMembership
    {
        return new WorkspaceMembership(
            userId: $this->userId,
            workspaceId: Uuid::v7(),
            role: $role,
        );
    }

    private function makeProject(bool $ownedByCurrentUser): Project&Stub
    {
        $ownerUserId = $ownedByCurrentUser ? $this->userId : Uuid::v7();

        $ownerUser = $this->createStub(User::class);
        $ownerUser->method('getId')->willReturn($ownerUserId);

        $ownerUW = $this->createStub(UserWorkspace::class);
        $ownerUW->method('getUser')->willReturn($ownerUser);

        $project = $this->createStub(Project::class);
        $project->method('getOwner')->willReturn($ownerUW);
        $project->method('getWorkspace')->willReturn($this->workspace);

        return $project;
    }
}
