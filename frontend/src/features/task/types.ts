export type TaskStatus = 'todo' | 'in-progress' | 'done'

export type TaskStats = {
    open: number
    in_progress: number
    done_this_week: number
}

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
    creatorId: string
    dueDate: string | null
    isOverdue: boolean
}

export type TaskFormValues = {
    title: string
    description?: string
    assigneeId?: string
    status?: TaskStatus
    dueDate?: string
}

export type UseTaskFormOptions = {
    workspaceId: string
    projectId: string
    onSuccess: () => void
} & (
    | { mode?: 'create'; taskId?: undefined; initialValues?: undefined }
    | { mode: 'edit'; taskId: string; initialValues: TaskFormValues }
)

export type TaskDetail = {
    id: string
    title: string
    description: string | null
    status: TaskStatus
    creator: TaskAssignee
    assignee: TaskAssignee | null
    createdAt: string
    updatedAt: string
    dueDate: string | null
    isOverdue: boolean
}

export type CreatePayload = {
    title: string
    description?: string
    assigneeId?: string
    dueDate?: string
}

export type UpdatePayload = CreatePayload & {
    status?: string
    removeAssignee?: boolean
    removeDueDate?: boolean
}
