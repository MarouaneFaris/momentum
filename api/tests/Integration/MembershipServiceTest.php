<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\WorkspaceRole;
use App\Factory\UserFactory;
use App\Factory\UserWorkspaceFactory;
use App\Factory\WorkspaceFactory;
use App\Factory\WorkspaceInvitationFactory;
use App\Repository\UserRepository;
use App\Repository\UserWorkspaceRepository;
use App\Repository\WorkspaceInvitationRepository;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class MembershipServiceTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private MockClock $clock;
    private MembershipService $service;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $container = static::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        /** @var WorkspaceInvitationRepository $invitationRepo */
        $invitationRepo = $container->get(WorkspaceInvitationRepository::class);

        /** @var UserWorkspaceRepository $uwRepo */
        $uwRepo = $container->get(UserWorkspaceRepository::class);

        /** @var UserRepository $userRepo */
        $userRepo = $container->get(UserRepository::class);

        $this->service = new MembershipService($this->clock, $em, $invitationRepo, $uwRepo, $userRepo);
    }

    // — invite ————————————————————————————————————————————————————————————————

    public function testInviteCreatesInvitationWithCorrectFields(): void
    {
        $ownerMembership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);
        $invitee = UserFactory::createOne(['email' => 'invitee@example.com']);

        $invitation = $this->service->invite(
            $ownerMembership->getWorkspace(),
            $ownerMembership->getUser(),
            'invitee@example.com',
            WorkspaceRole::Member,
        );

        self::assertNotNull($invitation->getId());
        self::assertSame(WorkspaceRole::Member, $invitation->getRole());
        self::assertSame((string) $invitee->getId(), (string) $invitation->getInvitee()->getId());
        self::assertSame((string) $ownerMembership->getUser()->getId(), (string) $invitation->getInvitedBy()?->getId());
    }

    public function testInviteThrows422WhenEmailNotFound(): void
    {
        $ownerMembership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->service->invite(
            $ownerMembership->getWorkspace(),
            $ownerMembership->getUser(),
            'nobody@example.com',
            WorkspaceRole::Member,
        );
    }

    public function testInviteThrows409WhenUserAlreadyMember(): void
    {
        $existingMembership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Member]);
        $ownerMembership = UserWorkspaceFactory::createOne([
            'workspace' => $existingMembership->getWorkspace(),
            'role' => WorkspaceRole::Owner,
        ]);

        $this->expectException(ConflictHttpException::class);
        $this->service->invite(
            $existingMembership->getWorkspace(),
            $ownerMembership->getUser(),
            $existingMembership->getUser()->getEmail(),
            WorkspaceRole::Member,
        );
    }

    public function testInviteThrows409WhenPendingInviteExists(): void
    {
        $ownerMembership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);
        $invitee = UserFactory::createOne();

        WorkspaceInvitationFactory::createOne([
            'workspace' => $ownerMembership->getWorkspace(),
            'invitee' => $invitee,
            'expiresAt' => (new \DateTimeImmutable())->modify('+7 days'),
        ]);

        $this->expectException(ConflictHttpException::class);
        $this->service->invite(
            $ownerMembership->getWorkspace(),
            $ownerMembership->getUser(),
            $invitee->getEmail(),
            WorkspaceRole::Member,
        );
    }

    public function testInviteReplacesExpiredInvitation(): void
    {
        $ownerMembership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);
        $invitee = UserFactory::createOne(['email' => 'invitee@example.com']);

        WorkspaceInvitationFactory::createOne([
            'workspace' => $ownerMembership->getWorkspace(),
            'invitee' => $invitee,
            'expiresAt' => (new \DateTimeImmutable())->modify('-1 day'),
        ]);

        $newInvitation = $this->service->invite(
            $ownerMembership->getWorkspace(),
            $ownerMembership->getUser(),
            'invitee@example.com',
            WorkspaceRole::Guest,
        );

        self::assertSame(WorkspaceRole::Guest, $newInvitation->getRole());

        /** @var WorkspaceInvitationRepository $repo */
        $repo = static::getContainer()->get(WorkspaceInvitationRepository::class);
        self::assertCount(1, $repo->findAll());
    }

    // — accept ————————————————————————————————————————————————————————————————

    public function testAcceptCreatesMembershipAndDeletesInvitation(): void
    {
        $workspace = WorkspaceFactory::createOne();
        $invitee = UserFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne([
            'workspace' => $workspace,
            'invitee' => $invitee,
            'role' => WorkspaceRole::Member,
        ]);

        $this->service->accept($invitation, $invitee);

        /** @var UserWorkspaceRepository $uwRepo */
        $uwRepo = static::getContainer()->get(UserWorkspaceRepository::class);
        $role = $uwRepo->findRoleByUserAndWorkspace($invitee, $workspace);
        self::assertSame(WorkspaceRole::Member, $role);

        /** @var WorkspaceInvitationRepository $invRepo */
        $invRepo = static::getContainer()->get(WorkspaceInvitationRepository::class);
        self::assertCount(0, $invRepo->findAll());
    }

    public function testAcceptThrows410WhenExpired(): void
    {
        $invitee = UserFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne([
            'invitee' => $invitee,
            'expiresAt' => (new \DateTimeImmutable())->modify('-1 hour'),
        ]);

        $this->expectException(GoneHttpException::class);
        $this->service->accept($invitation, $invitee);
    }

    public function testAcceptThrows403ForWrongInvitee(): void
    {
        $invitation = WorkspaceInvitationFactory::createOne();
        $otherUser = UserFactory::createOne();

        $this->expectException(AccessDeniedHttpException::class);
        $this->service->accept($invitation, $otherUser);
    }

    // — decline ———————————————————————————————————————————————————————————————

    public function testDeclineDeletesInvitation(): void
    {
        $invitee = UserFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne(['invitee' => $invitee]);

        $this->service->decline($invitation, $invitee);

        /** @var WorkspaceInvitationRepository $repo */
        $repo = static::getContainer()->get(WorkspaceInvitationRepository::class);
        self::assertCount(0, $repo->findAll());
    }

    public function testDeclineThrows403ForWrongInvitee(): void
    {
        $invitation = WorkspaceInvitationFactory::createOne();
        $otherUser = UserFactory::createOne();

        $this->expectException(AccessDeniedHttpException::class);
        $this->service->decline($invitation, $otherUser);
    }

    // — cancel ————————————————————————————————————————————————————————————————

    public function testCancelDeletesInvitation(): void
    {
        $workspace = WorkspaceFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne(['workspace' => $workspace]);

        $this->service->cancel($workspace, $invitation);

        /** @var WorkspaceInvitationRepository $repo */
        $repo = static::getContainer()->get(WorkspaceInvitationRepository::class);
        self::assertCount(0, $repo->findAll());
    }

    public function testCancelThrows404WhenInvitationBelongsToDifferentWorkspace(): void
    {
        $workspace = WorkspaceFactory::createOne();
        $invitation = WorkspaceInvitationFactory::createOne();

        $this->expectException(NotFoundHttpException::class);
        $this->service->cancel($workspace, $invitation);
    }

    // — removeMember ——————————————————————————————————————————————————————————

    public function testRemoveMemberDeletesMembership(): void
    {
        $membership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Member]);

        $this->service->removeMember($membership);

        /** @var UserWorkspaceRepository $repo */
        $repo = static::getContainer()->get(UserWorkspaceRepository::class);
        $found = $repo->findRoleByUserAndWorkspace($membership->getUser(), $membership->getWorkspace());
        self::assertNull($found);
    }

    public function testRemoveMemberThrows400WhenTargetIsOwner(): void
    {
        $membership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);

        $this->expectException(BadRequestHttpException::class);
        $this->service->removeMember($membership);
    }

    // — changeRole ————————————————————————————————————————————————————————————

    public function testChangeRoleUpdatesRole(): void
    {
        $actor = UserFactory::createOne();
        $workspace = WorkspaceFactory::createOne();
        UserWorkspaceFactory::createOne(['user' => $actor, 'workspace' => $workspace, 'role' => WorkspaceRole::Owner]);
        $membership = UserWorkspaceFactory::createOne(['workspace' => $workspace, 'role' => WorkspaceRole::Member]);

        $this->service->changeRole($membership, WorkspaceRole::Guest, $actor);

        $this->em->refresh($membership);
        self::assertSame(WorkspaceRole::Guest, $membership->getRole());
    }

    public function testChangeRoleThrows422WhenTargetRoleIsOwner(): void
    {
        $actor = UserFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Member]);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->service->changeRole($membership, WorkspaceRole::Owner, $actor);
    }

    public function testChangeRoleThrows400WhenActorChangesOwnRole(): void
    {
        $actor = UserFactory::createOne();
        $membership = UserWorkspaceFactory::createOne(['user' => $actor, 'role' => WorkspaceRole::Owner]);

        $this->expectException(BadRequestHttpException::class);
        $this->service->changeRole($membership, WorkspaceRole::Member, $actor);
    }

    // — leave —————————————————————————————————————————————————————————————————

    public function testLeaveDeletesMembership(): void
    {
        $membership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Member]);

        $this->service->leave($membership->getWorkspace(), $membership->getUser());

        /** @var UserWorkspaceRepository $repo */
        $repo = static::getContainer()->get(UserWorkspaceRepository::class);
        $found = $repo->findRoleByUserAndWorkspace($membership->getUser(), $membership->getWorkspace());
        self::assertNull($found);
    }

    public function testLeaveThrows403WhenCallerIsOwner(): void
    {
        $membership = UserWorkspaceFactory::createOne(['role' => WorkspaceRole::Owner]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->service->leave($membership->getWorkspace(), $membership->getUser());
    }
}
