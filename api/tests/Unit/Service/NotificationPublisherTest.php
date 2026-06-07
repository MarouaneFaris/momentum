<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Service\NotificationPublisher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Uid\Uuid;

final class NotificationPublisherTest extends TestCase
{
    private HubInterface&MockObject $hub;
    private LoggerInterface&MockObject $logger;
    private NotificationPublisher $publisher;

    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->publisher = new NotificationPublisher($this->hub, $this->logger);
    }

    public function testPublishCreatedSuccessDoesNotLog(): void
    {
        $this->hub->expects($this->once())->method('publish')->with($this->isInstanceOf(Update::class));
        $this->logger->expects($this->never())->method('warning');

        $this->publisher->publishCreated($this->makeNotification());
    }

    public function testPublishCreatedEnvelopeHasCreatedOp(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update): bool {
                $data = json_decode($update->getData(), true);
                self::assertSame('created', $data['op']);
                self::assertArrayHasKey('notification', $data);

                return true;
            }));

        $this->publisher->publishCreated($this->makeNotification());
    }

    public function testPublishCreatedFailureLogsWarningWithRequiredContext(): void
    {
        $exception = new \RuntimeException('hub down');
        $this->hub->expects($this->once())->method('publish')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Mercure publish failed',
                $this->callback(function (array $context) use ($exception): bool {
                    self::assertArrayHasKey('notification_id', $context);
                    self::assertArrayHasKey('recipient_id', $context);
                    self::assertArrayHasKey('topic', $context);
                    self::assertArrayHasKey('exception_class', $context);
                    self::assertArrayHasKey('exception_message', $context);
                    self::assertArrayHasKey('exception', $context);
                    self::assertSame(\RuntimeException::class, $context['exception_class']);
                    self::assertSame('hub down', $context['exception_message']);
                    self::assertSame($exception, $context['exception']);

                    return true;
                }),
            );

        $this->publisher->publishCreated($this->makeNotification());
    }

    public function testPublishUpdatedEnvelopeHasUpdatedOp(): void
    {
        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update): bool {
                $data = json_decode($update->getData(), true);
                self::assertSame('updated', $data['op']);
                self::assertArrayHasKey('notification', $data);

                return true;
            }));

        $this->publisher->publishUpdated($this->makeNotification());
    }

    public function testPublishUpdatedFailureIsSwallowed(): void
    {
        $this->hub->expects($this->once())->method('publish')->willThrowException(new \RuntimeException('hub down'));
        $this->logger->expects($this->once())->method('warning');

        $this->publisher->publishUpdated($this->makeNotification());
    }

    public function testPublishDeletedEnvelopeHasDeletedOp(): void
    {
        $id = Uuid::v4();
        $recipient = new User();

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use ($id): bool {
                $data = json_decode($update->getData(), true);
                self::assertSame('deleted', $data['op']);
                self::assertSame((string) $id, $data['id']);

                return true;
            }));

        $this->publisher->publishDeleted($id, $recipient);
    }

    public function testPublishDeletedFailureIsSwallowed(): void
    {
        $this->hub->expects($this->once())->method('publish')->willThrowException(new \RuntimeException('hub down'));
        $this->logger->expects($this->once())->method('warning');

        $this->publisher->publishDeleted(Uuid::v4(), new User());
    }

    public function testPublishAllReadEnvelopeHasAllReadOp(): void
    {
        $readAt = new \DateTimeImmutable('2026-06-07T12:00:00+00:00');
        $recipient = new User();

        $this->hub->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use ($readAt): bool {
                $data = json_decode($update->getData(), true);
                self::assertSame('all-read', $data['op']);
                self::assertSame($readAt->format(\DateTimeInterface::ATOM), $data['readAt']);

                return true;
            }));

        $this->publisher->publishAllRead($recipient, $readAt);
    }

    public function testPublishAllReadFailureIsSwallowed(): void
    {
        $this->hub->expects($this->once())->method('publish')->willThrowException(new \RuntimeException('hub down'));
        $this->logger->expects($this->once())->method('warning');

        $this->publisher->publishAllRead(new User(), new \DateTimeImmutable());
    }

    private function makeNotification(): Notification
    {
        $recipient = new User();

        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setType(NotificationType::InvitationReceived);
        $notification->setPayload([]);

        return $notification;
    }
}
