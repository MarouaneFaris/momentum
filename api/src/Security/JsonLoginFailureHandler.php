<?php

declare(strict_types=1);

namespace App\Security;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\Security\Exception\EmailNotVerifiedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private ErrorResponseFactory $factory) {}

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof EmailNotVerifiedException) {
            return $this->factory->build(ErrorCode::EMAIL_NOT_VERIFIED, 'Email address not verified.', [], Response::HTTP_FORBIDDEN);
        }

        return $this->factory->build(ErrorCode::AUTH_INVALID_CREDENTIALS, 'Invalid credentials.', [], Response::HTTP_UNAUTHORIZED);
    }
}
