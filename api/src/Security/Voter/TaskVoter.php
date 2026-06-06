<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\WorkspaceRole;
use App\Repository\UserProjectRepository;
use App\Repository\UserWorkspaceRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Project|Task> */
final class TaskVoter extends Voter
{
    public const string VIEW = 'task.view';
    public const string CREATE = 'task.create';
    public const string EDIT = 'task.edit';

    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
        private readonly UserProjectRepository $userProjectRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::VIEW) {
            return $subject instanceof Project || $subject instanceof Task;
        }

        if ($attribute === self::EDIT) {
            return $subject instanceof Task;
        }

        return $attribute === self::CREATE && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $project = $subject instanceof Task ? $subject->getProject() : $subject;

        $membership = $this->userWorkspaceRepository->findOneBy([
            'user' => $user,
            'workspace' => $project->getWorkspace(),
        ]);

        if ($membership === null) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $membership->getRole() !== WorkspaceRole::Guest;
        }

        if ($attribute === self::EDIT) {
            // Guest cannot edit any task
            return $membership->getRole() !== WorkspaceRole::Guest;
        }

        // VIEW: Owner and Member always granted; Guest needs project assignment
        if ($membership->getRole() !== WorkspaceRole::Guest) {
            return true;
        }

        return $this->userProjectRepository->findOneBy([
            'user' => $user,
            'project' => $project,
        ]) !== null;
    }
}
