<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\WorkspaceRole;
use App\Event\ProjectOwnerRemoved;
use App\Repository\ProjectRepository;
use App\Repository\UserWorkspaceRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class ProjectOwnerRemovedHandler
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    public function __invoke(ProjectOwnerRemoved $event): void
    {
        $projects = $this->projectRepository->findBy(['owner' => $event->removedMembership]);

        if ($projects === []) {
            return;
        }

        $ownerMembership = $this->userWorkspaceRepository->findOneBy([
            'workspace' => $event->workspace,
            'role' => WorkspaceRole::Owner,
        ]);

        if ($ownerMembership === null) {
            return;
        }

        foreach ($projects as $project) {
            $project->setOwner($ownerMembership);
        }
    }
}
