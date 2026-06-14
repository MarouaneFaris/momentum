<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Voter\WorkspaceVoter;
use App\Security\WorkspaceMembership;
use App\Security\WorkspaceMembershipResolver;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

final class WorkspaceVoterTest extends TestCase
{
    private WorkspaceVoter $voter;
    private WorkspaceMembershipResolver&Stub $resolver;
    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        $this->resolver = $this->createStub(WorkspaceMembershipResolver::class);
        $this->voter = new WorkspaceVoter($this->resolver);
        $this->user = new User();
        $this->workspace = new Workspace();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRoleAttributeCombinations')]
    public function testVoteOnAttributeWithRole(WorkspaceRole $role, string $attribute, int $expectedVote): void
    {
        $this->resolver
            ->method('for')
            ->willReturn($this->makeMembership($role));

        $result = $this->voter->vote($this->createToken(), $this->workspace, [$attribute]);

        self::assertSame($expectedVote, $result);
    }

    /** @return iterable<string, array{WorkspaceRole, string, int}> */
    public static function provideRoleAttributeCombinations(): iterable
    {
        yield 'owner can view' => [WorkspaceRole::Owner, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'owner can edit' => [WorkspaceRole::Owner, WorkspaceVoter::EDIT, VoterInterface::ACCESS_GRANTED];
        yield 'owner can delete' => [WorkspaceRole::Owner, WorkspaceVoter::DELETE, VoterInterface::ACCESS_GRANTED];
        yield 'owner can invite' => [WorkspaceRole::Owner, WorkspaceVoter::INVITE, VoterInterface::ACCESS_GRANTED];
        yield 'owner can cancel invitation' => [WorkspaceRole::Owner, WorkspaceVoter::CANCEL_INVITATION, VoterInterface::ACCESS_GRANTED];
        yield 'owner can view invitations' => [WorkspaceRole::Owner, WorkspaceVoter::VIEW_INVITATIONS, VoterInterface::ACCESS_GRANTED];
        yield 'owner can view members' => [WorkspaceRole::Owner, WorkspaceVoter::VIEW_MEMBERS, VoterInterface::ACCESS_GRANTED];
        yield 'owner can remove member' => [WorkspaceRole::Owner, WorkspaceVoter::REMOVE_MEMBER, VoterInterface::ACCESS_GRANTED];
        yield 'owner can change role' => [WorkspaceRole::Owner, WorkspaceVoter::CHANGE_ROLE, VoterInterface::ACCESS_GRANTED];

        yield 'member can view' => [WorkspaceRole::Member, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'member cannot edit' => [WorkspaceRole::Member, WorkspaceVoter::EDIT, VoterInterface::ACCESS_DENIED];
        yield 'member cannot delete' => [WorkspaceRole::Member, WorkspaceVoter::DELETE, VoterInterface::ACCESS_DENIED];
        yield 'member cannot invite' => [WorkspaceRole::Member, WorkspaceVoter::INVITE, VoterInterface::ACCESS_DENIED];
        yield 'member cannot cancel invitation' => [WorkspaceRole::Member, WorkspaceVoter::CANCEL_INVITATION, VoterInterface::ACCESS_DENIED];
        yield 'member cannot view invitations' => [WorkspaceRole::Member, WorkspaceVoter::VIEW_INVITATIONS, VoterInterface::ACCESS_DENIED];
        yield 'member can view members' => [WorkspaceRole::Member, WorkspaceVoter::VIEW_MEMBERS, VoterInterface::ACCESS_GRANTED];
        yield 'member cannot remove member' => [WorkspaceRole::Member, WorkspaceVoter::REMOVE_MEMBER, VoterInterface::ACCESS_DENIED];
        yield 'member cannot change role' => [WorkspaceRole::Member, WorkspaceVoter::CHANGE_ROLE, VoterInterface::ACCESS_DENIED];

        yield 'guest can view' => [WorkspaceRole::Guest, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'guest cannot edit' => [WorkspaceRole::Guest, WorkspaceVoter::EDIT, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot delete' => [WorkspaceRole::Guest, WorkspaceVoter::DELETE, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot invite' => [WorkspaceRole::Guest, WorkspaceVoter::INVITE, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot cancel invitation' => [WorkspaceRole::Guest, WorkspaceVoter::CANCEL_INVITATION, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot view invitations' => [WorkspaceRole::Guest, WorkspaceVoter::VIEW_INVITATIONS, VoterInterface::ACCESS_DENIED];
        yield 'guest can view members' => [WorkspaceRole::Guest, WorkspaceVoter::VIEW_MEMBERS, VoterInterface::ACCESS_GRANTED];
        yield 'guest cannot remove member' => [WorkspaceRole::Guest, WorkspaceVoter::REMOVE_MEMBER, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot change role' => [WorkspaceRole::Guest, WorkspaceVoter::CHANGE_ROLE, VoterInterface::ACCESS_DENIED];
    }

    public function testNonMemberIsDenied(): void
    {
        $this->resolver
            ->method('for')
            ->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->workspace, [WorkspaceVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainsOnUnsupportedSubject(): void
    {
        $result = $this->voter->vote($this->createToken(), new \stdClass(), [WorkspaceVoter::VIEW]);

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
            userId: Uuid::v7(),
            workspaceId: Uuid::v7(),
            role: $role,
        );
    }
}
