<?php

declare(strict_types=1);

namespace App\Security;

enum Capability: string
{
    case WORKSPACE_VIEW = 'workspace.view';
    case WORKSPACE_RENAME = 'workspace.edit';
    case WORKSPACE_DELETE = 'workspace.delete';
    case MEMBER_INVITE = 'workspace.invite';
    case MEMBER_CANCEL_INVITATION = 'workspace.cancel_invitation';
    case MEMBER_VIEW_INVITATIONS = 'workspace.view_invitations';
    case MEMBER_VIEW = 'workspace.view_members';
    case MEMBER_REMOVE = 'workspace.remove_member';
    case MEMBER_CHANGE_ROLE = 'workspace.change_role';
    case LEAVE_WORKSPACE = 'workspace.leave';
    case PROJECT_VIEW = 'project.view';
    case PROJECT_CREATE = 'project.create';
    case PROJECT_EDIT = 'project.edit';
    case PROJECT_DELETE = 'project.delete';
    case PROJECT_MANAGE_MEMBERS = 'project.manage_members';
    case TASK_VIEW = 'task.view';
    case TASK_CREATE = 'task.create';
    case TASK_EDIT = 'task.edit';
    case TASK_STATUS_CHANGE = 'task.status_change';
    case TASK_DELETE = 'task.delete';
}
