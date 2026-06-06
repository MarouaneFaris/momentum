export type TaskStatus = 'todo' | 'in-progress' | 'done'

export type TaskAssignee = {
    id: string
    name: string
}

export type Task = {
    id: string
    title: string
    status: TaskStatus
    assignee: TaskAssignee | null
    createdAt: string
}
