import { useProjects } from '@/features/project/queries'
import { useWorkspace } from '@/features/workspace/queries'
import { useParams } from 'react-router'
import { useWorkspaceProjectTasks } from '../queries'
import type { Task, TaskStatus } from '../types'

export type TasksByStatus = Record<TaskStatus, Task[]>

export function useWorkspaceProjectTasksPage() {
    const { id: workspaceId, projectId } = useParams<{ id: string; projectId: string }>()
    const { data: tasks, isLoading } = useWorkspaceProjectTasks(workspaceId!, projectId!)
    const { data: workspace } = useWorkspace(workspaceId!)
    const { data: projects } = useProjects(workspaceId!)

    const isGuest = workspace?.role === 'guest'
    const projectName = projects?.find((p) => p.id === projectId)?.name ?? null

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
        tasks: tasks ?? [],
        tasksByStatus,
        isEmpty: tasks != null && tasks.length === 0,
        isGuest,
        isOwner: workspace?.role === 'owner',
        projectName,
    }
}
