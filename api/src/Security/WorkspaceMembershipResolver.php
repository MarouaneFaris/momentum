<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Entity\Workspace;
use App\Repository\UserWorkspaceRepository;
use Symfony\Contracts\Service\ResetInterface;

class WorkspaceMembershipResolver implements ResetInterface
{
    /** @var array<string, WorkspaceMembership|null> */
    private array $cache = [];

    public function __construct(
        private readonly UserWorkspaceRepository $userWorkspaceRepository,
    ) {}

    /**
     * The cache is a per-request memo (the voter and controller both resolve the
     * same membership). Under FrankenPHP worker mode the service instance is reused
     * across requests, so it MUST be cleared between them — otherwise a membership
     * cached before a user is removed keeps granting access after removal. The
     * kernel.reset autoconfiguration (ResetInterface) calls this between requests.
     */
    public function reset(): void
    {
        $this->cache = [];
    }

    public function for(User $user, Workspace $workspace): ?WorkspaceMembership
    {
        $userId = $user->getId();
        $workspaceId = $workspace->getId();

        if ($userId === null || $workspaceId === null) {
            return null;
        }

        $key = $userId->toRfc4122() . '/' . $workspaceId->toRfc4122();

        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        $uw = $this->userWorkspaceRepository->findOneBy(['user' => $user, 'workspace' => $workspace]);

        return $this->cache[$key] = $uw === null ? null : new WorkspaceMembership(
            userId: $userId,
            workspaceId: $workspaceId,
            role: $uw->getRole(),
        );
    }
}
