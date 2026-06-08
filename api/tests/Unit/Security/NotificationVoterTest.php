<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Security\Voter\NotificationVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

final class NotificationVoterTest extends TestCase
{
    private NotificationVoter $voter;
    private User $user;

    protected function setUp(): void
    {
        $this->voter = new NotificationVoter();
        $this->user = new User();
        $this->setId($this->user, Uuid::v4());
    }

    public function testUnauthenticatedUserDenied(): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $notification = $this->makeNotification($this->user);

        $result = $this->voter->vote($token, $notification, [NotificationVoter::UPDATE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAbstainOnWrongSubject(): void
    {
        $result = $this->voter->vote($this->createToken(), new \stdClass(), [NotificationVoter::UPDATE]);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testAbstainOnWrongAttribute(): void
    {
        $notification = $this->makeNotification($this->user);

        $result = $this->voter->vote($this->createToken(), $notification, ['wrong.attribute']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /** @return iterable<string, array{string}> */
    public static function provideAttributes(): iterable
    {
        yield 'update' => [NotificationVoter::UPDATE];
        yield 'delete' => [NotificationVoter::DELETE];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAttributes')]
    public function testOwnerGranted(string $attribute): void
    {
        $notification = $this->makeNotification($this->user);

        $result = $this->voter->vote($this->createToken(), $notification, [$attribute]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAttributes')]
    public function testOtherUserDenied(string $attribute): void
    {
        $otherUser = new User();
        $this->setId($otherUser, Uuid::v4());
        $notification = $this->makeNotification($otherUser);

        $result = $this->voter->vote($this->createToken(), $notification, [$attribute]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAttributes')]
    public function testSameIdDifferentRefGranted(string $attribute): void
    {
        $sharedId = Uuid::v4();

        $recipient = new User();
        $this->setId($recipient, $sharedId);

        $tokenUser = new User();
        $this->setId($tokenUser, $sharedId);

        $notification = $this->makeNotification($recipient);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($tokenUser);

        self::assertNotSame($recipient, $tokenUser);
        $result = $this->voter->vote($token, $notification, [$attribute]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideAttributes')]
    public function testNullRecipientIdDenied(string $attribute): void
    {
        $recipientWithNullId = new User();
        $notification = $this->makeNotification($recipientWithNullId);

        $result = $this->voter->vote($this->createToken(), $notification, [$attribute]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    private function makeNotification(User $recipient): Notification
    {
        $notification = new Notification();
        $notification->setRecipient($recipient);
        $notification->setType(NotificationType::TaskAssignedToYou);
        $notification->setPayload([]);

        return $notification;
    }

    private function createToken(): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->user);

        return $token;
    }

    private function setId(User $user, Uuid $id): void
    {
        $ref = new \ReflectionProperty(User::class, 'id');
        $ref->setValue($user, $id);
    }
}
