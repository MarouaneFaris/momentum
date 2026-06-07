export type NotificationType =
    | 'task_assigned_to_you'
    | 'task_assigned_member'
    | 'task_status_changed_yours'
    | 'task_status_changed_member'
    | 'invitation_received'
    | 'invitation_accepted'
    | 'invitation_declined'

export type TaskAssignedToYouPayload = {
    task_id: string
    task_title: string
}

export type TaskAssignedMemberPayload = {
    task_id: string
    task_title: string
    assignee_name: string
}

export type TaskStatusChangedYoursPayload = {
    task_id: string
    task_title: string
    new_status: string
}

export type TaskStatusChangedMemberPayload = {
    task_id: string
    task_title: string
    new_status: string
    actor_name: string
}

export type InvitationReceivedPayload = {
    workspace_name: string
    role_name: string
}

export type InvitationAcceptedPayload = {
    workspace_name: string
    actor_name: string
}

export type InvitationDeclinedPayload = {
    workspace_name: string
    actor_name: string
}

export type NotificationPayload =
    | TaskAssignedToYouPayload
    | TaskAssignedMemberPayload
    | TaskStatusChangedYoursPayload
    | TaskStatusChangedMemberPayload
    | InvitationReceivedPayload
    | InvitationAcceptedPayload
    | InvitationDeclinedPayload

export type Notification = {
    id: string
    type: NotificationType
    payload: NotificationPayload
    readAt: string | null
    createdAt: string
}
