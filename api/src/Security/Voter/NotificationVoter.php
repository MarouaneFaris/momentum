<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Notification;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Notification> */
final class NotificationVoter extends Voter
{
    public const string UPDATE = 'notification.update';
    public const string DELETE = 'notification.delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::UPDATE, self::DELETE], true)
            && $subject instanceof Notification;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $recipient = $subject->getRecipient();
        $recipientId = $recipient->getId();
        $userId = $user->getId();

        return $recipient === $user
            || ($recipientId !== null && $userId !== null && $recipientId->equals($userId));
    }
}
