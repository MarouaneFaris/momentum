<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\Entity\User;
use App\Notification\NotificationOrchestrator;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MarkAllNotificationsReadController extends AbstractController
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    #[OA\Patch(
        path: '/api/notifications/read-all',
        summary: 'Mark all notifications as read',
        responses: [
            new OA\Response(response: 204, description: 'All notifications marked as read'),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    #[Route(
        path: '/api/notifications/read-all',
        name: 'api_notifications_mark_all_read',
        methods: Request::METHOD_PATCH,
    )]
    public function __invoke(
        #[CurrentUser] User $user,
    ): Response {
        $this->orchestrator->allNotificationsRead($user, new \DateTimeImmutable());

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
