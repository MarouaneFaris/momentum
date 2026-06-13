<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class JsonExceptionListener
{
    public function __construct(
        private ErrorResponseFactory $factory,
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApiException) {
            $event->setResponse(
                $this->factory->build($exception->errorCode, $exception->getMessage(), $exception->context, $exception->httpStatus),
            );

            return;
        }

        if ($exception instanceof AccessDeniedException) {
            $token = $this->tokenStorage->getToken();
            $isAuthenticated = $token !== null && $token->getUser() !== null;

            if (!$isAuthenticated) {
                $event->setResponse(
                    $this->factory->build(ErrorCode::AUTH_NOT_AUTHENTICATED, 'Authentication required.', [], Response::HTTP_UNAUTHORIZED),
                );
            } else {
                $event->setResponse(
                    $this->factory->build(ErrorCode::WORKSPACE_FORBIDDEN, 'Access denied.', [], Response::HTTP_FORBIDDEN),
                );
            }

            return;
        }

        if ($exception instanceof AuthenticationException) {
            $event->setResponse(
                $this->factory->build(ErrorCode::AUTH_NOT_AUTHENTICATED, 'Authentication required.', [], Response::HTTP_UNAUTHORIZED),
            );

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $code = $this->codeForStatus($exception->getStatusCode());
            $response = $this->factory->build($code, $exception->getMessage(), [], $exception->getStatusCode());

            foreach ($exception->getHeaders() as $name => $value) {
                $response->headers->set($name, $value);
            }

            $event->setResponse($response);

            if ($exception->getStatusCode() >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                $this->logError($event, $exception);
            }

            return;
        }

        $this->logError($event, $exception);
        $event->setResponse(
            $this->factory->build(ErrorCode::INTERNAL_ERROR, 'An unexpected error occurred.', [], Response::HTTP_INTERNAL_SERVER_ERROR),
        );
    }

    private function codeForStatus(int $status): ErrorCode
    {
        return match (true) {
            $status === Response::HTTP_UNAUTHORIZED => ErrorCode::AUTH_NOT_AUTHENTICATED,
            $status === Response::HTTP_FORBIDDEN => ErrorCode::WORKSPACE_FORBIDDEN,
            $status === Response::HTTP_NOT_FOUND => ErrorCode::WORKSPACE_NOT_FOUND,
            $status === Response::HTTP_TOO_MANY_REQUESTS => ErrorCode::RATE_LIMITED,
            $status >= Response::HTTP_BAD_REQUEST && $status < Response::HTTP_INTERNAL_SERVER_ERROR => ErrorCode::VALIDATION_FAILED,
            default => ErrorCode::INTERNAL_ERROR,
        };
    }

    private function logError(ExceptionEvent $event, \Throwable $exception): void
    {
        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        $this->logger->error('Unhandled exception', [
            'error' => $exception->getMessage(),
            'exception_class' => $exception::class,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'user_id' => $token?->getUserIdentifier(),
        ]);
    }
}
