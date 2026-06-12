<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\Exception\ApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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
                    $this->factory->build(ErrorCode::AUTH_NOT_AUTHENTICATED, 'Authentication required.', [], 401),
                );
            } else {
                $event->setResponse(
                    $this->factory->build(ErrorCode::WORKSPACE_FORBIDDEN, 'Access denied.', [], 403),
                );
            }

            return;
        }

        if ($exception instanceof AuthenticationException) {
            $event->setResponse(
                $this->factory->build(ErrorCode::AUTH_NOT_AUTHENTICATED, 'Authentication required.', [], 401),
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

            if ($exception->getStatusCode() >= 500) {
                $this->logError($event, $exception);
            }

            return;
        }

        $this->logError($event, $exception);
        $event->setResponse(
            $this->factory->build(ErrorCode::INTERNAL_ERROR, 'An unexpected error occurred.', [], 500),
        );
    }

    private function codeForStatus(int $status): ErrorCode
    {
        return match (true) {
            $status === 401 => ErrorCode::AUTH_NOT_AUTHENTICATED,
            $status === 403 => ErrorCode::WORKSPACE_FORBIDDEN,
            $status === 404 => ErrorCode::WORKSPACE_NOT_FOUND,
            $status === 429 => ErrorCode::RATE_LIMITED,
            $status >= 400 && $status < 500 => ErrorCode::VALIDATION_FAILED,
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
