<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationType: string
{
    case TaskAssignedToYou = 'task_assigned_to_you';
    case TaskAssignedMember = 'task_assigned_member';
    case TaskStatusChangedYours = 'task_status_changed_yours';
    case TaskStatusChangedMember = 'task_status_changed_member';
    case InvitationReceived = 'invitation_received';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationDeclined = 'invitation_declined';
}
