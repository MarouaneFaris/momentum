<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\UserRemovedFromWorkspace;
use App\Repository\UserProjectRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class UserRemovedFromWorkspaceHandler
{
    public function __construct(
        private UserProjectRepository $userProjectRepository,
    ) {}

    public function __invoke(UserRemovedFromWorkspace $event): void
    {
        $this->userProjectRepository->deleteByUserAndWorkspace(
            $event->removedUser,
            $event->workspace,
        );
    }
}
