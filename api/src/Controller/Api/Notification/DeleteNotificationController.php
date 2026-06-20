<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\Entity\Notification;
use App\Notification\NotificationOrchestrator;
use App\Security\Voter\NotificationVoter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DeleteNotificationController extends AbstractController
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    #[OA\Delete(
        path: '/api/notifications/{id}',
        summary: 'Delete a notification',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Notification deleted'),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    #[Route(
        path: '/api/notifications/{id}',
        name: 'api_notifications_delete',
        methods: Request::METHOD_DELETE,
    )]
    #[IsGranted(NotificationVoter::DELETE, subject: 'notification')]
    public function __invoke(
        Notification $notification,
    ): Response {
        $this->orchestrator->notificationDeleted($notification);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
