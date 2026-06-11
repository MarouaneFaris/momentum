export type InvitationRole = 'member' | 'guest'

export type WorkspaceRole = 'owner' | 'member' | 'guest'

export type InvitationInvitee = {
    id: string
    name: string
    email: string
}

export type InvitationWorkspace = {
    id: string
    name: string
}

export type InvitationInvitedBy = {
    id: string
    name: string
}

export type InvitationStatus = 'pending' | 'expired'

export type InvitationOwnerView = {
    id: string
    invitee: InvitationInvitee
    role: InvitationRole
    status: InvitationStatus
    expiresAt: string
    createdAt: string
}

export type InvitationInviteeView = {
    id: string
    workspace: InvitationWorkspace
    invitedBy: InvitationInvitedBy | null
    role: InvitationRole
    expiresAt: string
    createdAt: string
}

export type Member = {
    id: string
    name: string
    email: string
    role: WorkspaceRole
    joinedAt: string
}
