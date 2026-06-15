<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\Repository\EmailVerificationTokenRepository;
use App\Service\AuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VerifyEmailController extends AbstractController
{
    public function __construct(private readonly ErrorResponseFactory $errorFactory) {}

    #[OA\Get(
        path: '/api/verify-email',
        summary: 'Verify email address via token from verification email',
        parameters: [
            new OA\Parameter(name: 'token', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully'),
            new OA\Response(response: 400, description: 'Token invalid or expired'),
        ]
    )]
    #[Route(
        path: '/api/verify-email',
        name: 'api_verify_email',
        methods: Request::METHOD_GET,
    )]
    public function __invoke(
        Request $request,
        EmailVerificationTokenRepository $tokenRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $rawToken = (string) $request->query->get('token', '');
        if ($rawToken === '') {
            return $this->errorFactory->build(ErrorCode::EMAIL_TOKEN_INVALID, 'Missing token.', [], Response::HTTP_BAD_REQUEST);
        }

        $tokenHash = AuthTokenManager::hashToken($rawToken);
        $token = $tokenRepository->findByHash($tokenHash);

        if ($token === null || $token->isUsed()) {
            return $this->errorFactory->build(ErrorCode::EMAIL_TOKEN_INVALID, 'Token is invalid or has already been used.', [], Response::HTTP_BAD_REQUEST);
        }

        if ($token->isExpired()) {
            return $this->errorFactory->build(ErrorCode::EMAIL_TOKEN_EXPIRED, 'Token has expired. Please request a new verification email.', [], Response::HTTP_BAD_REQUEST);
        }

        $user = $token->getUser();
        $now = new \DateTimeImmutable();

        $token->setUsedAt($now);
        $user->setEmailVerifiedAt($now);

        $entityManager->flush();

        return $this->json(['message' => 'Email verified successfully.']);
    }
}
