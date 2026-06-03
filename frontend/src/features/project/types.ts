export type ProjectStatus = 'draft' | 'active' | 'archived'

export type Project = {
    id: string
    name: string
    description: string | null
    status: ProjectStatus
    ownerUserId: string
    createdAt: string
    updatedAt: string
}

export type UseProjectFormOptions = {
    workspaceId: string
    project?: Project
    onSuccess: () => void
}
