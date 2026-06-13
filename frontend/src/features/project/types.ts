export type ProjectStatus = 'draft' | 'active' | 'archived'

export type ProjectColorKey = 'blue' | 'green' | 'amber' | 'red' | 'purple' | 'neutral'

export type Project = {
    id: string
    name: string
    description: string | null
    status: ProjectStatus
    color: ProjectColorKey
    ownerUserId: string
    createdAt: string
    updatedAt: string
}

export type ProjectMember = {
    id: string
    name: string
    email: string
    assignedAt: string
}

export type UseProjectFormOptions = {
    workspaceId: string
    project?: Project
    onSuccess: () => void
}
