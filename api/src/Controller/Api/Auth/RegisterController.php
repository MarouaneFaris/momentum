<?php

declare(strict_types=1);

namespace App\Controller\Api\Auth;

use App\DTO\RegisterDTO;
use App\Enum\ErrorCode;
use App\Factory\ApiErrorResponseFactory;
use App\Service\RegisterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterController extends AbstractController
{
    #[Route(
        path: '/api/register',
        name: 'api_register',
        methods: Request::METHOD_POST,
    )]
    public function index(
        Request $request,
        ValidatorInterface $validator,
        RegisterService $registerService,
        ApiErrorResponseFactory $errorFactory,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $errorFactory->create(
                ErrorCode::VALIDATION_FAILED,
                'Invalid JSON.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (!is_array($data)) {
            return $errorFactory->create(
                ErrorCode::VALIDATION_FAILED,
                'Invalid request body.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $dto = new RegisterDTO(
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            password: is_string($data['password'] ?? null) ? $data['password'] : '',
        );

        $violations = $validator->validate($dto);
        if (count($violations) > 0) {
            $violationList = [];
            foreach ($violations as $violation) {
                $violationList[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            return $errorFactory->create(
                ErrorCode::VALIDATION_FAILED,
                'Validation failed.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['violations' => $violationList],
            );
        }

        try {
            $registered = $registerService->register($dto);
        } catch (\Throwable) {
            return $errorFactory->create(
                ErrorCode::REGISTRATION_FAILED,
                'Registration failed.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        if (!$registered) {
            return $errorFactory->create(
                ErrorCode::REGISTRATION_FAILED,
                'Registration failed.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->json([
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], Response::HTTP_CREATED);
    }
}
