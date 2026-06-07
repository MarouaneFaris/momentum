<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MercureTokenController extends AbstractController
{
    public function __construct(private readonly HubInterface $hub) {}

    #[OA\Get(
        path: '/api/notifications/mercure-token',
        summary: 'Get a Mercure subscriber JWT scoped to the caller\'s notification topic',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscriber JWT',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'token', type: 'string')],
                    type: 'object'
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    #[Route(
        path: '/api/notifications/mercure-token',
        name: 'api_notifications_mercure_token',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $factory = $this->hub->getFactory();
        \assert(null !== $factory, 'Mercure token factory is not configured');

        $token = $factory->create(
            subscribe: ["/notifications/{$user->getId()}"],
            publish: [],
        );

        return $this->json(['token' => $token]);
    }
}
