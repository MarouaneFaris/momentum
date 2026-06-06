import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { Task, TaskDetail } from './types'

export const useWorkspaceProjectTasks = (workspaceId: string, projectId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks'],
        queryFn: () => api.get<Task[]>(`/workspaces/${workspaceId}/projects/${projectId}/tasks`),
    })

export const useTask = (workspaceId: string, projectId: string, taskId: string | null) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks', taskId],
        queryFn: () =>
            api.get<TaskDetail>(`/workspaces/${workspaceId}/projects/${projectId}/tasks/${taskId}`),
        enabled: taskId !== null,
    })

export const useCreateTask = (workspaceId: string, projectId: string) =>
    useMutation({
        mutationFn: (data: { title: string; description?: string; assigneeId?: string }) =>
            api.post<Task>(`/workspaces/${workspaceId}/projects/${projectId}/tasks`, data),
    })
