<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class RequestContextProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        return $record->with(extra: array_merge($record->extra, [
            'request_id' => $request?->headers->get('X-Request-ID', '') ?? '',
            'user_id' => $this->tokenStorage->getToken()?->getUserIdentifier(),
            'env' => $this->environment,
        ]));
    }
}
