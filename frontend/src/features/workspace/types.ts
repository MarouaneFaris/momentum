export type WorkspaceRole = 'owner' | 'member' | 'guest'

export type Workspace = {
    id: string
    name: string
    createdAt: string
    role: WorkspaceRole
}
