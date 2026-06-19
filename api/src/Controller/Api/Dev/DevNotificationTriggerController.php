<?php

declare(strict_types=1);

namespace App\Controller\Api\Dev;

use App\Entity\User;
use App\Enum\NotificationType;
use App\Service\DevService;
use App\Service\NotificationPublisher;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class DevNotificationTriggerController extends AbstractController
{
    public function __construct(
        private readonly DevService $devService,
        private readonly NotificationService $notificationService,
        private readonly NotificationPublisher $notificationPublisher,
    ) {}

    #[Route(
        path: '/api/dev/notifications/trigger',
        name: 'api_dev_notifications_trigger',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        Request $request,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->devService->ensureDevEnvironment();

        $typeValue = $request->toArray()['type'] ?? null;
        $type = $typeValue !== null ? NotificationType::tryFrom($typeValue) : null;

        if ($type === null) {
            return $this->json(['error' => 'type required and must be a valid NotificationType value'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $fakeId = '00000000-0000-0000-0000-000000000001';
        $fakeProjectId = '00000000-0000-0000-0000-000000000002';
        $fakeWorkspaceId = '00000000-0000-0000-0000-000000000003';

        $payload = match ($type) {
            NotificationType::TaskAssignedToYou => [
                'task_id' => $fakeId,
                'task_title' => 'Demo Task',
                'project_id' => $fakeProjectId,
                'project_name' => 'Demo Project',
                'workspace_id' => $fakeWorkspaceId,
                'demo' => true,
            ],
            NotificationType::TaskAssignedMember => [
                'task_id' => $fakeId,
                'task_title' => 'Demo Task',
                'assignee_name' => 'Demo Member',
                'project_id' => $fakeProjectId,
                'project_name' => 'Demo Project',
                'workspace_id' => $fakeWorkspaceId,
                'demo' => true,
            ],
            NotificationType::TaskStatusChangedYours => [
                'task_id' => $fakeId,
                'task_title' => 'Demo Task',
                'new_status' => 'in_progress',
                'project_id' => $fakeProjectId,
                'project_name' => 'Demo Project',
                'workspace_id' => $fakeWorkspaceId,
                'demo' => true,
            ],
            NotificationType::TaskStatusChangedMember => [
                'task_id' => $fakeId,
                'task_title' => 'Demo Task',
                'new_status' => 'done',
                'actor_name' => 'Demo Member',
                'project_id' => $fakeProjectId,
                'project_name' => 'Demo Project',
                'workspace_id' => $fakeWorkspaceId,
                'demo' => true,
            ],
            NotificationType::InvitationReceived => [
                'workspace_name' => 'Demo Workspace',
                'role_name' => 'member',
                'demo' => true,
            ],
            NotificationType::InvitationAccepted => [
                'workspace_name' => 'Demo Workspace',
                'actor_name' => 'Demo User',
                'demo' => true,
            ],
            NotificationType::InvitationDeclined => [
                'workspace_name' => 'Demo Workspace',
                'actor_name' => 'Demo User',
                'demo' => true,
            ],
            NotificationType::InvitationCancelled => [
                'workspace_name' => 'Demo Workspace',
                'role_name' => 'member',
                'demo' => true,
            ],
        };

        $notification = $this->notificationService->create($user, $type, $payload);
        $this->notificationPublisher->publishCreated($notification);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
