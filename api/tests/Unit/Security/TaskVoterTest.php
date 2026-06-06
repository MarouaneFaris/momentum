<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserProjectRepository;
use App\Repository\UserWorkspaceRepository;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;
    private UserWorkspaceRepository&Stub $workspaceRepo;
    private UserProjectRepository&Stub $projectRepo;
    private User $user;
    private Workspace $workspace;
    private Project $project;

    protected function setUp(): void
    {
        $this->workspaceRepo = $this->createStub(UserWorkspaceRepository::class);
        $this->projectRepo = $this->createStub(UserProjectRepository::class);
        $this->voter = new TaskVoter($this->workspaceRepo, $this->projectRepo);

        $this->user = new User();
        $this->workspace = new Workspace();
        $this->project = new Project();
        $this->project->setWorkspace($this->workspace);
    }

    public function testUnauthenticatedUserDenied(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonMemberDenied(): void
    {
        $this->workspaceRepo->method('findOneBy')->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOwnerMemberRoles')]
    public function testOwnerAndMemberGranted(WorkspaceRole $role): void
    {
        $membership = $this->makeMembership($role);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /** @return iterable<string, array{WorkspaceRole}> */
    public static function provideOwnerMemberRoles(): iterable
    {
        yield 'owner' => [WorkspaceRole::Owner];
        yield 'member' => [WorkspaceRole::Member];
    }

    public function testGuestWithProjectAssignmentGranted(): void
    {
        $membership = $this->makeMembership(WorkspaceRole::Guest);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);

        $userProject = new UserProject();
        $this->projectRepo->method('findOneBy')->willReturn($userProject);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testGuestWithoutProjectAssignmentDenied(): void
    {
        $membership = $this->makeMembership(WorkspaceRole::Guest);
        $this->workspaceRepo->method('findOneBy')->willReturn($membership);
        $this->projectRepo->method('findOneBy')->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainOnWrongAttribute(): void
    {
        $result = $this->voter->vote($this->createToken(), $this->project, ['wrong.attribute']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    private function createToken(): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        return $token;
    }

    private function makeMembership(WorkspaceRole $role): UserWorkspace
    {
        $m = new UserWorkspace();
        $m->setUser($this->user);
        $m->setWorkspace($this->workspace);
        $m->setRole($role);

        return $m;
    }
}
