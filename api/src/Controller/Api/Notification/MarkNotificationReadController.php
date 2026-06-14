<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\DTO\Response\NotificationResponse;
use App\Entity\Notification;
use App\Notification\NotificationOrchestrator;
use App\Security\Voter\NotificationVoter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MarkNotificationReadController extends AbstractController
{
    #[OA\Patch(
        path: '/api/notifications/{id}/read',
        summary: 'Mark a notification as read',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification marked as read',
                content: new OA\JsonContent(ref: new Model(type: NotificationResponse::class))
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    #[Route(
        path: '/api/notifications/{id}/read',
        name: 'api_notifications_mark_read',
        methods: Request::METHOD_PATCH,
    )]
    #[IsGranted(NotificationVoter::UPDATE, subject: 'notification')]
    public function __invoke(
        Notification $notification,
        NotificationOrchestrator $orchestrator,
    ): JsonResponse {
        $orchestrator->notificationRead($notification);

        return $this->json(NotificationResponse::fromNotification($notification));
    }
}
