<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Enum\WorkspaceRole;
use App\Event\WorkspaceInvitationAccepted;
use App\Event\WorkspaceInvitationCancelled;
use App\Event\WorkspaceInvitationCreated;
use App\Event\WorkspaceInvitationDeclined;
use App\EventListener\InvitationAcceptedNotificationHandler;
use App\EventListener\InvitationCancelledNotificationHandler;
use App\EventListener\InvitationCreatedNotificationHandler;
use App\EventListener\InvitationDeclinedNotificationHandler;
use App\Notification\NotificationOrchestrator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InvitationNotificationHandlerTest extends TestCase
{
    private NotificationOrchestrator&MockObject $orchestrator;

    protected function setUp(): void
    {
        $this->orchestrator = $this->createMock(NotificationOrchestrator::class);
    }

    public function testCreatedDelegates(): void
    {
        $invitation = $this->makeInvitation();

        $this->orchestrator->expects($this->once())->method('invitationCreated')->with($invitation);

        $handler = new InvitationCreatedNotificationHandler($this->orchestrator);
        ($handler)(new WorkspaceInvitationCreated($invitation));
    }

    public function testAcceptedDelegates(): void
    {
        $actor = new User();
        $invitation = $this->makeInvitation();

        $this->orchestrator->expects($this->once())->method('invitationAccepted')->with($invitation, $actor);

        $handler = new InvitationAcceptedNotificationHandler($this->orchestrator);
        ($handler)(new WorkspaceInvitationAccepted($invitation, $actor));
    }

    public function testDeclinedDelegates(): void
    {
        $actor = new User();
        $invitation = $this->makeInvitation();

        $this->orchestrator->expects($this->once())->method('invitationDeclined')->with($invitation, $actor);

        $handler = new InvitationDeclinedNotificationHandler($this->orchestrator);
        ($handler)(new WorkspaceInvitationDeclined($invitation, $actor));
    }

    public function testCancelledDelegates(): void
    {
        $actor = new User();
        $invitation = $this->makeInvitation();

        $this->orchestrator->expects($this->once())->method('invitationCancelled')->with($invitation);

        $handler = new InvitationCancelledNotificationHandler($this->orchestrator);
        ($handler)(new WorkspaceInvitationCancelled($invitation, $actor));
    }

    private function makeInvitation(
        ?User $invitee = null,
        ?User $invitedBy = null,
        WorkspaceRole $role = WorkspaceRole::Member,
    ): WorkspaceInvitation {
        $workspace = new Workspace();
        $workspace->setName('Test Workspace');

        $invitation = new WorkspaceInvitation();
        $invitation->setWorkspace($workspace);
        $invitation->setInvitee($invitee ?? new User());
        $invitation->setInvitedBy($invitedBy);
        $invitation->setRole($role);
        $invitation->setCreatedAt(new \DateTimeImmutable());
        $invitation->setExpiresAt(new \DateTimeImmutable('+7 days'));

        return $invitation;
    }
}
