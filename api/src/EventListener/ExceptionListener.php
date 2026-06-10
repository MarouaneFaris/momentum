<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return;
        }

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
