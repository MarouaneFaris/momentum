<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Error\ErrorCode;
use App\Error\ErrorResponseFactory;
use App\EventListener\JsonExceptionListener;
use App\Exception\ApiException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class JsonExceptionListenerTest extends TestCase
{
    private JsonExceptionListener $listener;
    private LoggerInterface&Stub $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createStub(LoggerInterface::class);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);

        $this->listener = new JsonExceptionListener(
            new ErrorResponseFactory(),
            $this->logger,
            $tokenStorage,
        );
    }

    public function testApiExceptionSetsCodedResponse(): void
    {
        $event = $this->makeEvent(new ApiException(ErrorCode::VALIDATION_FAILED, 'Bad input.', ['field' => 'name']));
        ($this->listener)($event);

        $response = $this->getResponse($event);
        self::assertSame(422, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('VALIDATION_FAILED', $body['code']);
        self::assertSame('Bad input.', $body['message']);
        self::assertSame(['field' => 'name'], $body['context']);
    }

    public function testApiExceptionCustomStatus(): void
    {
        $event = $this->makeEvent(new ApiException(ErrorCode::WORKSPACE_FORBIDDEN, 'Forbidden.', [], 403));
        ($this->listener)($event);

        self::assertSame(403, $this->getResponse($event)->getStatusCode());
    }

    public function test401HttpExceptionMapsToAuthNotAuthenticated(): void
    {
        $event = $this->makeEvent(new UnauthorizedHttpException('Bearer', 'Unauthorized.'));
        ($this->listener)($event);

        $response = $this->getResponse($event);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('AUTH_NOT_AUTHENTICATED', $body['code']);
        self::assertSame(401, $response->getStatusCode());
    }

    public function test403HttpExceptionMapsToWorkspaceForbidden(): void
    {
        $event = $this->makeEvent(new AccessDeniedHttpException('Forbidden.'));
        ($this->listener)($event);

        $response = $this->getResponse($event);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('WORKSPACE_FORBIDDEN', $body['code']);
        self::assertSame(403, $response->getStatusCode());
    }

    public function test404HttpExceptionMapsToWorkspaceNotFound(): void
    {
        $event = $this->makeEvent(new NotFoundHttpException('Not found.'));
        ($this->listener)($event);

        $response = $this->getResponse($event);
        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('WORKSPACE_NOT_FOUND', $body['code']);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testUncaughtThrowableReturns500WithInternalError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');
        $listener = new JsonExceptionListener(new ErrorResponseFactory(), $logger, $this->createStub(TokenStorageInterface::class));

        $event = $this->makeEvent(new \RuntimeException('Something blew up.'));
        $listener($event);

        $response = $this->getResponse($event);
        self::assertSame(500, $response->getStatusCode());

        /** @var array<string, mixed> $body */
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('INTERNAL_ERROR', $body['code']);
    }

    public function testApiExceptionDoesNotLog(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $listener = new JsonExceptionListener(new ErrorResponseFactory(), $logger, $this->createStub(TokenStorageInterface::class));

        $event = $this->makeEvent(new ApiException(ErrorCode::VALIDATION_FAILED, 'Nope.'));
        $listener($event);
    }

    public function test4xxHttpExceptionDoesNotLog(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');
        $listener = new JsonExceptionListener(new ErrorResponseFactory(), $logger, $this->createStub(TokenStorageInterface::class));

        $event = $this->makeEvent(new NotFoundHttpException('Not found.'));
        $listener($event);
    }

    private function makeEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, Request::create('/api/test'), HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    private function getResponse(ExceptionEvent $event): Response
    {
        $response = $event->getResponse();
        self::assertNotNull($response, 'Expected listener to set a response on the event.');

        return $response;
    }
}
