<?php

declare(strict_types=1);

namespace App\Controller\Api\Notification;

use App\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MercureTokenController extends AbstractController
{
    private const JWT_TTL = 3600;

    public function __construct(private readonly TokenFactoryInterface $subscriberJwtFactory) {}

    #[OA\Get(
        path: '/api/notifications/mercure-token',
        summary: 'Issue a Mercure subscriber cookie scoped to the caller\'s notification topic',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cookie set; body contains TTL in seconds',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'expiresIn', type: 'integer', example: 3600)],
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
        $now = new \DateTimeImmutable();
        $token = $this->subscriberJwtFactory->create(
            subscribe: ["/notifications/{$user->getId()}"],
            publish: [],
            additionalClaims: [
                'sub' => (string) $user->getId(),
                'iat' => $now,
                'exp' => $now->modify('+' . self::JWT_TTL . ' seconds'),
            ],
        );

        $response = $this->json(['expiresIn' => self::JWT_TTL]);
        $response->headers->setCookie(Cookie::create(
            name: 'mercureAuthorization',
            value: $token,
            expire: time() + self::JWT_TTL,
            path: '/.well-known/mercure',
            secure: true,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_STRICT,
        ));

        return $response;
    }
}
