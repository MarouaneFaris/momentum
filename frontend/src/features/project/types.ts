export type ProjectStatus = 'draft' | 'active' | 'archived'

export type Project = {
    id: string
    name: string
    description: string | null
    status: ProjectStatus
    createdAt: string
    updatedAt: string
}
