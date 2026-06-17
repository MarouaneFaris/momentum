<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\Service\EmailVerificationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class VerifyEmailController extends AbstractController
{
    #[OA\Post(
        path: '/api/verify-email',
        summary: 'Verify email address via token from verification email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 400, description: 'Token invalid or expired'),
        ]
    )]
    #[Route(
        path: '/api/verify-email',
        name: 'api_verify_email',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        Request $request,
        EmailVerificationService $emailVerificationService,
    ): JsonResponse {
        $rawToken = (string) ($request->toArray()['token'] ?? '');
        $emailVerificationService->verify($rawToken);

        return $this->json(['message' => 'Email verified successfully.']);
    }
}
