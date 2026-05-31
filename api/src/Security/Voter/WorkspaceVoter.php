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

/** @extends Voter<string, Workspace> */
final class WorkspaceVoter extends Voter
{
    public const string VIEW = 'workspace.view';
    public const string EDIT = 'workspace.edit';
    public const string DELETE = 'workspace.delete';
    public const string INVITE = 'workspace.invite';
    public const string CANCEL_INVITATION = 'workspace.cancel_invitation';
    public const string VIEW_INVITATIONS = 'workspace.view_invitations';
    public const string VIEW_MEMBERS = 'workspace.view_members';
    public const string REMOVE_MEMBER = 'workspace.remove_member';
    public const string CHANGE_ROLE = 'workspace.change_role';

    private const array ATTRIBUTES = [
        self::VIEW,
        self::EDIT,
        self::DELETE,
        self::INVITE,
        self::CANCEL_INVITATION,
        self::VIEW_INVITATIONS,
        self::VIEW_MEMBERS,
        self::REMOVE_MEMBER,
        self::CHANGE_ROLE,
    ];

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
            self::EDIT,
            self::DELETE,
            self::INVITE,
            self::CANCEL_INVITATION,
            self::VIEW_INVITATIONS,
            self::REMOVE_MEMBER,
            self::CHANGE_ROLE => $role === WorkspaceRole::Owner,
            self::VIEW_MEMBERS => true,
            default => false,
        };
    }
}
