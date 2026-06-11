<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Enum\WorkspaceRole;
use App\Event\ProjectMemberRemoved;
use App\Event\ProjectOwnerRemoved;
use App\Event\UserRemovedFromWorkspace;
use App\Event\WorkspaceInvitationAccepted;
use App\Event\WorkspaceInvitationCancelled;
use App\Event\WorkspaceInvitationCreated;
use App\Event\WorkspaceInvitationDeclined;
use App\Repository\UserRepository;
use App\Repository\UserWorkspaceRepository;
use App\Repository\WorkspaceInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class MembershipService
{
    public function __construct(
        private ClockInterface $clock,
        private EntityManagerInterface $em,
        private WorkspaceInvitationRepository $invitationRepository,
        private UserWorkspaceRepository $userWorkspaceRepository,
        private UserRepository $userRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function invite(Workspace $workspace, User $invitedBy, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        $invitee = $this->userRepository->findOneBy(['email' => $email]);
        if (!$invitee) {
            throw new UnprocessableEntityHttpException('No user found with this email');
        }

        if ($this->userWorkspaceRepository->findRoleByUserAndWorkspace($invitee, $workspace) !== null) {
            throw new ConflictHttpException('User is already a member of this workspace');
        }

        $now = $this->clock->now();
        $existing = $this->invitationRepository->findByWorkspaceAndInvitee($workspace, $invitee);

        if ($existing !== null) {
            if ($existing->getExpiresAt() > $now) {
                throw new ConflictHttpException('A pending invitation already exists for this user');
            }
            // Delete expired invitation before inserting the replacement;
            // Doctrine processes INSERTs before DELETEs in a single flush,
            // which would violate the (workspace_id, invitee_id) unique constraint.
            $this->em->remove($existing);
            $this->em->flush();
        }

        $invitation = new WorkspaceInvitation();
        $invitation->setWorkspace($workspace);
        $invitation->setInvitee($invitee);
        $invitation->setInvitedBy($invitedBy);
        $invitation->setRole($role);
        $invitation->setCreatedAt($now);
        $invitation->setExpiresAt($now->modify('+7 days'));

        $this->em->persist($invitation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new WorkspaceInvitationCreated($invitation));

        return $invitation;
    }

    public function accept(WorkspaceInvitation $invitation, User $currentUser): void
    {
        $now = $this->clock->now();

        if ($invitation->getExpiresAt() <= $now) {
            throw new GoneHttpException('Invitation has expired');
        }

        if ((string) $invitation->getInvitee()->getId() !== (string) $currentUser->getId()) {
            throw new AccessDeniedHttpException('This invitation belongs to another user');
        }

        $membership = new UserWorkspace();
        $membership->setUser($currentUser);
        $membership->setWorkspace($invitation->getWorkspace());
        $membership->setRole($invitation->getRole());

        $this->em->persist($membership);
        $this->em->remove($invitation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new WorkspaceInvitationAccepted($invitation, $currentUser));
    }

    public function decline(WorkspaceInvitation $invitation, User $currentUser): void
    {
        if ((string) $invitation->getInvitee()->getId() !== (string) $currentUser->getId()) {
            throw new AccessDeniedHttpException('This invitation belongs to another user');
        }

        $this->em->remove($invitation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new WorkspaceInvitationDeclined($invitation, $currentUser));
    }

    public function resend(Workspace $workspace, WorkspaceInvitation $invitation): void
    {
        if ((string) $invitation->getWorkspace()->getId() !== (string) $workspace->getId()) {
            throw new NotFoundHttpException('Invitation not found in this workspace');
        }

        $now = $this->clock->now();

        if ($invitation->getExpiresAt() <= $now) {
            throw new BadRequestHttpException('Cannot resend an expired invitation');
        }

        $invitation->setCreatedAt($now);
        $invitation->setExpiresAt($now->modify('+7 days'));

        $this->em->flush();

        $this->eventDispatcher->dispatch(new WorkspaceInvitationCreated($invitation));
    }

    public function cancel(Workspace $workspace, WorkspaceInvitation $invitation, User $actor): void
    {
        if ((string) $invitation->getWorkspace()->getId() !== (string) $workspace->getId()) {
            throw new NotFoundHttpException('Invitation not found in this workspace');
        }

        $this->em->remove($invitation);
        $this->em->flush();

        $this->eventDispatcher->dispatch(new WorkspaceInvitationCancelled($invitation, $actor));
    }

    public function removeMember(UserWorkspace $membership): void
    {
        if ($membership->getRole() === WorkspaceRole::Owner) {
            throw new BadRequestHttpException('Cannot remove workspace owner');
        }

        $this->em->remove($membership);
        $this->dispatchRemovalEvent($membership);
        $this->em->flush();
    }

    public function changeRole(UserWorkspace $membership, WorkspaceRole $newRole, User $actor): void
    {
        if ($newRole === WorkspaceRole::Owner) {
            throw new UnprocessableEntityHttpException('Role owner cannot be assigned via API');
        }

        if ((string) $membership->getUser()->getId() === (string) $actor->getId()) {
            throw new BadRequestHttpException('Owner cannot change their own role');
        }

        $membership->setRole($newRole);
        $this->em->flush();
    }

    public function leave(Workspace $workspace, User $user): void
    {
        $membership = $this->userWorkspaceRepository->findOneBy(['user' => $user, 'workspace' => $workspace]);

        if (!$membership) {
            throw new NotFoundHttpException('Membership not found');
        }

        if ($membership->getRole() === WorkspaceRole::Owner) {
            throw new AccessDeniedHttpException('Workspace owner cannot leave; delete the workspace instead');
        }

        $this->em->remove($membership);
        $this->dispatchRemovalEvent($membership);
        $this->em->flush();
    }

    private function dispatchRemovalEvent(UserWorkspace $membership): void
    {
        $workspace = $membership->getWorkspace();
        $this->eventDispatcher->dispatch(new ProjectOwnerRemoved($membership, $workspace));
        $this->eventDispatcher->dispatch(new ProjectMemberRemoved($membership, $workspace));
        $this->eventDispatcher->dispatch(new UserRemovedFromWorkspace($membership->getUser(), $workspace));
    }
}
