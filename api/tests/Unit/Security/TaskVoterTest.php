<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserProjectRepository;
use App\Security\Voter\TaskVoter;
use App\Security\WorkspaceMembership;
use App\Security\WorkspaceMembershipResolver;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

final class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;
    private WorkspaceMembershipResolver&Stub $resolver;
    private UserProjectRepository&Stub $projectRepo;
    private User $user;
    private Workspace $workspace;
    private Project $project;

    protected function setUp(): void
    {
        $this->resolver = $this->createStub(WorkspaceMembershipResolver::class);
        $this->projectRepo = $this->createStub(UserProjectRepository::class);
        $this->voter = new TaskVoter($this->resolver, $this->projectRepo);

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
        $this->resolver->method('for')->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOwnerMemberRoles')]
    public function testOwnerAndMemberGranted(WorkspaceRole $role): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership($role));

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
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $this->projectRepo->method('findOneBy')->willReturn(new UserProject());

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testGuestWithoutProjectAssignmentDenied(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $this->projectRepo->method('findOneBy')->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainOnWrongAttribute(): void
    {
        $result = $this->voter->vote($this->createToken(), $this->project, ['wrong.attribute']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOwnerMemberRoles')]
    public function testCreateGrantedForOwnerAndMember(WorkspaceRole $role): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership($role));

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::CREATE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testCreateDeniedForGuest(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::CREATE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testCreateDeniedForNonMember(): void
    {
        $this->resolver->method('for')->willReturn(null);

        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::CREATE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOwnerGrantedOnTaskSubject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Owner));
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonMemberDeniedOnTaskSubject(): void
    {
        $this->resolver->method('for')->willReturn(null);
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // EDIT tests

    #[\PHPUnit\Framework\Attributes\DataProvider('provideOwnerMemberRoles')]
    public function testEditGrantedForOwnerAndMember(WorkspaceRole $role): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership($role));
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditDeniedForGuest(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEditDeniedForNonMember(): void
    {
        $this->resolver->method('for')->willReturn(null);
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEditAbstainOnProjectSubject(): void
    {
        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::EDIT]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGuestWithProjectAssignmentGrantedOnTaskSubject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $this->projectRepo->method('findOneBy')->willReturn(new UserProject());
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testGuestWithoutProjectAssignmentDeniedOnTaskSubject(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $this->projectRepo->method('findOneBy')->willReturn(null);
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::VIEW]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    // DELETE tests

    public function testDeleteGrantedForOwner(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Owner));
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeleteGrantedForCreatorMember(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));
        $task = $this->makeTask(); // creator === $this->user

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeleteDeniedForMemberNonCreator(): void
    {
        $otherUser = new User();
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Member));

        $task = new Task();
        $task->setProject($this->project);
        $task->setCreator($otherUser); // different creator
        $task->setTitle('Task');

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeleteDeniedForGuest(): void
    {
        $this->resolver->method('for')->willReturn($this->makeMembership(WorkspaceRole::Guest));
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeleteDeniedForNonMember(): void
    {
        $this->resolver->method('for')->willReturn(null);
        $task = $this->makeTask();

        $result = $this->voter->vote($this->createToken(), $task, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeleteAbstainOnProjectSubject(): void
    {
        $result = $this->voter->vote($this->createToken(), $this->project, [TaskVoter::DELETE]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    private function makeTask(): Task
    {
        $task = new Task();
        $task->setProject($this->project);
        $task->setCreator($this->user);
        $task->setTitle('Test task');

        return $task;
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
