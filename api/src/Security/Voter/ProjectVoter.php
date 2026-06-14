<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Workspace;
use App\Enum\WorkspaceRole;
use App\Security\Capability;
use App\Security\WorkspaceMembershipResolver;
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
        private readonly WorkspaceMembershipResolver $resolver,
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

        $workspace = $subject instanceof Project ? $subject->getWorkspace() : $subject;
        $membership = $this->resolver->for($user, $workspace);

        if ($membership === null) {
            return false;
        }

        if ($subject instanceof Workspace) {
            return $membership->can(Capability::from($attribute));
        }

        /** @var Project $project */
        $project = $subject;

        // Base role-level check: Guest cannot edit/delete/manage any project
        if (!$membership->can(Capability::from($attribute))) {
            return false;
        }

        // Owner may act on any project
        if ($membership->role === WorkspaceRole::Owner) {
            return true;
        }

        // Member: only project creator may edit/delete/manage
        return $project->getOwner()->getUser()->getId()?->equals($membership->userId) ?? false;
    }
}
