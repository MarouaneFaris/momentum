<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\DTO\Response\NotificationResponse;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ListNotificationsController extends AbstractController
{
    #[OA\Get(
        path: '/api/notifications',
        summary: 'List notifications for the authenticated user',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notification list',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: NotificationResponse::class))
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    #[Route(
        path: '/api/notifications',
        name: 'api_notifications_list',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(
        #[CurrentUser] User $user,
        NotificationRepository $notificationRepository,
    ): JsonResponse {
        $notifications = $notificationRepository->findByRecipient($user);

        return $this->json(
            array_map(
                static fn (Notification $n) => NotificationResponse::fromNotification($n),
                $notifications,
            ),
        );
    }
}
