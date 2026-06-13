<?php

declare(strict_types=1);

namespace App\Sentry;

use Sentry\Event;
use Sentry\EventHint;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final readonly class EventFilter
{
    public function __construct(private Security $security) {}

    public function __invoke(Event $event, ?EventHint $hint): ?Event
    {
        $exception = $hint?->exception;

        if ($exception instanceof AccessDeniedException || $exception instanceof AuthenticationException) {
            if ($this->security->getUser() === null) {
                return null;
            }

            return $event;
        }

        if ($exception instanceof NotFoundHttpException) {
            $event->setTag('http.404', 'true');

            return $event;
        }

        return $event;
    }
}
