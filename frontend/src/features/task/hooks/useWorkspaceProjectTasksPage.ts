import { useWorkspace } from '@/features/workspace/queries'
import { useParams } from 'react-router'
import { useWorkspaceProjectTasks } from '../queries'
import type { Task, TaskStatus } from '../types'

export type TasksByStatus = Record<TaskStatus, Task[]>

export function useWorkspaceProjectTasksPage() {
    const { id: workspaceId, projectId } = useParams<{ id: string; projectId: string }>()
    const { data: tasks, isLoading } = useWorkspaceProjectTasks(workspaceId!, projectId!)
    const { data: workspace } = useWorkspace(workspaceId!)

    const isGuest = workspace?.role === 'guest'

    const tasksByStatus: TasksByStatus = {
        todo: [],
        'in-progress': [],
        done: [],
    }

    if (tasks) {
        for (const task of tasks) {
            tasksByStatus[task.status].push(task)
        }
    }

    return {
        workspaceId: workspaceId!,
        projectId: projectId!,
        isLoading,
        tasksByStatus,
        isEmpty: tasks != null && tasks.length === 0,
        isGuest,
    }
}
