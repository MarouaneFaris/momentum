<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\ResendVerificationDTO;
use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\Service\EmailVerificationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ResendVerificationController extends AbstractController
{
    public function __construct(
        private readonly ErrorResponseFactory $errorFactory,
        #[Autowire(service: 'limiter.resend_verification')]
        private readonly RateLimiterFactory $resendVerificationLimiter,
    ) {}

    #[OA\Post(
        path: '/api/resend-verification',
        summary: 'Resend email verification link',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'If the email exists and is unverified, a new link will be sent'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ]
    )]
    #[Route(
        path: '/api/resend-verification',
        name: 'api_resend_verification',
        methods: Request::METHOD_POST,
    )]
    public function __invoke(
        #[MapRequestPayload] ResendVerificationDTO $dto,
        Request $request,
        EmailVerificationService $emailVerificationService,
    ): JsonResponse {
        $limiter = $this->resendVerificationLimiter->create($dto->email . '|' . $request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            return $this->errorFactory->build(ErrorCode::RATE_LIMITED, 'Too many requests.', [], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $emailVerificationService->resend($dto->email);

        return $this->json(['message' => 'If this email is registered and unverified, a new link has been sent.']);
    }
}
