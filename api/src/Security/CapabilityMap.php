<?php

declare(strict_types=1);

namespace App\Security;

use App\Enum\WorkspaceRole;

/**
 * Single source of truth for the RBAC capability matrix.
 * See "Workspace Permissions Matrix" in api/CONTEXT.md.
 *
 * Note: some capabilities granted here require additional checks in voters
 * (e.g. PROJECT_EDIT for Member requires project-ownership check).
 * Use WorkspaceMembership::can() as the role-level floor; voters add finer conditions.
 */
final class CapabilityMap
{
    /** @var array<string, list<Capability>>|null */
    private static ?array $map = null;

    /** @return list<Capability> */
    public static function for(WorkspaceRole $role): array
    {
        self::$map ??= self::build();

        return self::$map[$role->value];
    }

    /** @return array<string, list<Capability>> */
    private static function build(): array
    {
        $ownerOnly = [
            Capability::WORKSPACE_RENAME,
            Capability::WORKSPACE_DELETE,
            Capability::MEMBER_INVITE,
            Capability::MEMBER_CANCEL_INVITATION,
            Capability::MEMBER_VIEW_INVITATIONS,
            Capability::MEMBER_REMOVE,
            Capability::MEMBER_CHANGE_ROLE,
        ];

        $memberAndAbove = [
            Capability::PROJECT_CREATE,
            Capability::PROJECT_EDIT,
            Capability::PROJECT_DELETE,
            Capability::PROJECT_MANAGE_MEMBERS,
            Capability::TASK_VIEW,
            Capability::TASK_CREATE,
            Capability::TASK_EDIT,
            Capability::TASK_STATUS_CHANGE,
            Capability::TASK_DELETE,
        ];

        $everyone = [
            Capability::WORKSPACE_VIEW,
            Capability::MEMBER_VIEW,
            Capability::PROJECT_VIEW,
        ];

        $nonOwner = [
            Capability::LEAVE_WORKSPACE,
        ];

        return [
            WorkspaceRole::Owner->value => [
                ...$everyone,
                ...$ownerOnly,
                ...$memberAndAbove,
            ],
            WorkspaceRole::Member->value => [
                ...$everyone,
                ...$nonOwner,
                ...$memberAndAbove,
            ],
            WorkspaceRole::Guest->value => [
                ...$everyone,
                ...$nonOwner,
                // TASK_VIEW is not here — Guest task visibility requires project assignment (handled in TaskVoter)
            ],
        ];
    }
}
