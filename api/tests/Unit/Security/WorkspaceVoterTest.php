<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\WorkspaceVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class WorkspaceVoterTest extends TestCase
{
    private WorkspaceVoter $voter;
    private UserWorkspaceRepository $repository;
    private User $user;
    private Workspace $workspace;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserWorkspaceRepository::class);
        $this->voter = new WorkspaceVoter($this->repository);
        $this->user = new User();
        $this->workspace = new Workspace();
    }

    /** @dataProvider provideRoleAttributeCombinations */
    public function testVoteOnAttributeWithRole(WorkspaceRole $role, string $attribute, int $expectedVote): void
    {
        $this->repository
            ->method('findRoleByUserAndWorkspace')
            ->willReturn($role);

        $result = $this->voter->vote($this->createToken(), $this->workspace, [$attribute]);

        self::assertSame($expectedVote, $result);
    }

    /** @return iterable<string, array{WorkspaceRole, string, int}> */
    public static function provideRoleAttributeCombinations(): iterable
    {
        yield 'owner can view' => [WorkspaceRole::Owner, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'owner can edit' => [WorkspaceRole::Owner, WorkspaceVoter::EDIT, VoterInterface::ACCESS_GRANTED];
        yield 'owner can delete' => [WorkspaceRole::Owner, WorkspaceVoter::DELETE, VoterInterface::ACCESS_GRANTED];

        yield 'member can view' => [WorkspaceRole::Member, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'member cannot edit' => [WorkspaceRole::Member, WorkspaceVoter::EDIT, VoterInterface::ACCESS_DENIED];
        yield 'member cannot delete' => [WorkspaceRole::Member, WorkspaceVoter::DELETE, VoterInterface::ACCESS_DENIED];

        yield 'guest can view' => [WorkspaceRole::Guest, WorkspaceVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'guest cannot edit' => [WorkspaceRole::Guest, WorkspaceVoter::EDIT, VoterInterface::ACCESS_DENIED];
        yield 'guest cannot delete' => [WorkspaceRole::Guest, WorkspaceVoter::DELETE, VoterInterface::ACCESS_DENIED];
    }

    public function testNonMemberIsdenied(): void
    {
        $this->repository
            ->method('findRoleByUserAndWorkspace')
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
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        return $token;
    }
}
