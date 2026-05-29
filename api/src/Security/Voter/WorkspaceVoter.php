<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class WorkspaceVoter extends Voter
{
    public const string VIEW = 'workspace.view';
    public const string EDIT = 'workspace.edit';
    public const string DELETE = 'workspace.delete';

    private const array ATTRIBUTES = [self::VIEW, self::EDIT, self::DELETE];

    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, self::ATTRIBUTES, true) && $subject instanceof Workspace;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Workspace $workspace */
        $workspace = $subject;
        $role = $this->userWorkspaceRepository->findRoleByUserAndWorkspace($user, $workspace);

        if ($role === null) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::EDIT, self::DELETE => $role === WorkspaceRole::Owner,
            default => false,
        };
    }
}
