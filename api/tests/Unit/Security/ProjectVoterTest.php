<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\ProjectVoter;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

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

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRoleAttributeCombinations')]
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
        yield 'owner can view' => [WorkspaceRole::Owner, ProjectVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'member can view' => [WorkspaceRole::Member, ProjectVoter::VIEW, VoterInterface::ACCESS_GRANTED];
        yield 'guest can view' => [WorkspaceRole::Guest, ProjectVoter::VIEW, VoterInterface::ACCESS_GRANTED];
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
}
