<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Repository\UserWorkspaceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Workspace|Project> */
final class ProjectVoter extends Voter
{
    public const string VIEW = 'project.view';
    public const string CREATE = 'project.create';
    public const string EDIT = 'project.edit';
    public const string DELETE = 'project.delete';
    public const string MANAGE_MEMBERS = 'project.manage_members';

    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match (true) {
            \in_array($attribute, [self::VIEW, self::CREATE], true) && $subject instanceof Workspace => true,
            \in_array($attribute, [self::EDIT, self::DELETE, self::MANAGE_MEMBERS], true) && $subject instanceof Project => true,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($subject instanceof Project) {
            $membership = $this->userWorkspaceRepository->findOneBy([
                'user' => $user,
                'workspace' => $subject->getWorkspace(),
            ]);

            if ($membership === null) {
                return false;
            }

            if ($membership->getRole() === WorkspaceRole::Guest) {
                return false;
            }

            if ($membership->getRole() === WorkspaceRole::Owner) {
                return true;
            }

            return (string) $subject->getOwner()->getId() === (string) $membership->getId();
        }

        /** @var Workspace $workspace */
        $workspace = $subject;
        $role = $this->userWorkspaceRepository->findRoleByUserAndWorkspace($user, $workspace);

        if ($role === null) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => true,
            self::CREATE => $role === WorkspaceRole::Owner || $role === WorkspaceRole::Member,
            default => false,
        };
    }
}
