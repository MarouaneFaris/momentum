<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\Message\SendVerificationEmail;
use App\Repository\UserRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class ResendVerificationController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MessageBusInterface $bus,
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
    public function __invoke(Request $request): JsonResponse
    {
        $email = mb_strtolower((string) ($request->toArray()['email'] ?? ''));

        $limiter = $this->resendVerificationLimiter->create($email);
        if (!$limiter->consume()->isAccepted()) {
            return $this->json(['code' => 'RATE_LIMITED', 'message' => 'Too many requests.', 'context' => []], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user !== null && !$user->isEmailVerified()) {
            $this->bus->dispatch(new SendVerificationEmail((string) $user->getId()));
        }

        return $this->json(['message' => 'If this email is registered and unverified, a new link has been sent.']);
    }
}
