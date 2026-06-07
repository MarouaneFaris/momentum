<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Workspace;
use App\Entity\WorkspaceInvitation;
use App\Enum\NotificationType;
use App\Enum\WorkspaceRole;
use App\Event\WorkspaceInvitationAccepted;
use App\Event\WorkspaceInvitationCreated;
use App\Event\WorkspaceInvitationDeclined;
use App\EventListener\InvitationAcceptedNotificationHandler;
use App\EventListener\InvitationCreatedNotificationHandler;
use App\EventListener\InvitationDeclinedNotificationHandler;
use App\Service\NotificationPublisher;
use App\Service\NotificationServiceInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InvitationNotificationHandlerTest extends TestCase
{
    private NotificationServiceInterface&MockObject $notificationService;
    private NotificationPublisher&MockObject $notificationPublisher;

    protected function setUp(): void
    {
        $this->notificationService = $this->createMock(NotificationServiceInterface::class);
        $this->notificationPublisher = $this->createMock(NotificationPublisher::class);
    }

    public function testCreatedHandlerNotifiesInvitee(): void
    {
        $invitee = new User();
        $invitation = $this->makeInvitation(invitee: $invitee, role: WorkspaceRole::Member);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($invitee, NotificationType::InvitationReceived, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($notification);

        $handler = new InvitationCreatedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationCreated($invitation));
    }

    public function testAcceptedHandlerNotifiesInviter(): void
    {
        $inviter = new User();
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($inviter, NotificationType::InvitationAccepted, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($notification);

        $handler = new InvitationAcceptedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationAccepted($invitation, $actor));
    }

    public function testAcceptedHandlerSkipsWhenNoInviter(): void
    {
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: null);

        $this->notificationService->expects($this->never())->method('create');
        $this->notificationPublisher->expects($this->never())->method('publish');

        $handler = new InvitationAcceptedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationAccepted($invitation, $actor));
    }

    public function testDeclinedHandlerNotifiesInviter(): void
    {
        $inviter = new User();
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);

        $notification = new Notification();

        $this->notificationService
            ->expects($this->once())
            ->method('create')
            ->with($inviter, NotificationType::InvitationDeclined, $this->isArray())
            ->willReturn($notification);

        $this->notificationPublisher
            ->expects($this->once())
            ->method('publish')
            ->with($notification);

        $handler = new InvitationDeclinedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationDeclined($invitation, $actor));
    }

    public function testDeclinedHandlerSkipsWhenNoInviter(): void
    {
        $actor = new User();
        $invitation = $this->makeInvitation(invitedBy: null);

        $this->notificationService->expects($this->never())->method('create');
        $this->notificationPublisher->expects($this->never())->method('publish');

        $handler = new InvitationDeclinedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationDeclined($invitation, $actor));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCreatedHandlerMercureFailureSilenced(): void
    {
        $this->expectNotToPerformAssertions();

        $invitee = new User();
        $invitation = $this->makeInvitation(invitee: $invitee);

        $notification = new Notification();
        $notification->setRecipient($invitee);
        $this->notificationService->method('create')->willReturn($notification);
        $this->notificationPublisher->method('publish')->willThrowException(new \RuntimeException('hub down'));

        $handler = new InvitationCreatedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationCreated($invitation));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testAcceptedHandlerMercureFailureSilenced(): void
    {
        $this->expectNotToPerformAssertions();

        $inviter = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);

        $notification = new Notification();
        $notification->setRecipient($inviter);
        $this->notificationService->method('create')->willReturn($notification);
        $this->notificationPublisher->method('publish')->willThrowException(new \RuntimeException('hub down'));

        $handler = new InvitationAcceptedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationAccepted($invitation, new User()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testDeclinedHandlerMercureFailureSilenced(): void
    {
        $this->expectNotToPerformAssertions();

        $inviter = new User();
        $invitation = $this->makeInvitation(invitedBy: $inviter);

        $notification = new Notification();
        $notification->setRecipient($inviter);
        $this->notificationService->method('create')->willReturn($notification);
        $this->notificationPublisher->method('publish')->willThrowException(new \RuntimeException('hub down'));

        $handler = new InvitationDeclinedNotificationHandler($this->notificationService, $this->notificationPublisher, new NullLogger());
        ($handler)(new WorkspaceInvitationDeclined($invitation, new User()));
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
