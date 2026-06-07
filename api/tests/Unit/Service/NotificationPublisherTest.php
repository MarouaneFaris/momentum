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

    public function testSuccessPathDoesNotLog(): void
    {
        $notification = $this->makeNotification();

        $this->hub->expects($this->once())->method('publish')->with($this->isInstanceOf(Update::class));
        $this->logger->expects($this->never())->method('warning');

        $this->publisher->publish($notification);
    }

    public function testFailurePathLogsWarningWithRequiredContext(): void
    {
        $notification = $this->makeNotification();
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

        $this->publisher->publish($notification);
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
