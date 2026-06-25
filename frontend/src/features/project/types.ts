export type ProjectStatus = 'draft' | 'active' | 'archived'

export type ProjectColorKey = 'blue' | 'green' | 'amber' | 'red' | 'purple' | 'neutral'

export type ProjectTaskStats = {
    total: number
    done: number
    open: number
}

export type Project = {
    id: string
    name: string
    description: string | null
    status: ProjectStatus
    color: ProjectColorKey
    ownerUserId: string
    createdAt: string
    updatedAt: string
    taskStats: ProjectTaskStats
    memberNames: string[]
}

export type ProjectDetailTaskStats = {
    total: number
    todo: number
    inProgress: number
    done: number
}

export type ProjectDetail = {
    id: string
    name: string
    description: string | null
    status: ProjectStatus
    color: ProjectColorKey
    ownerUserId: string
    createdAt: string
    updatedAt: string
    taskStats: ProjectDetailTaskStats
    memberCount: number
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
