import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { Task, TaskDetail, TaskStats, TaskStatus } from './types'

export const useWorkspaceTaskStats = (workspaceId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'tasks', 'stats'],
        queryFn: () => api.get<TaskStats>(`/workspaces/${workspaceId}/tasks/stats`),
    })

export const useWorkspaceMyTasks = (workspaceId: string, limit?: number) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'tasks', 'my', limit],
        queryFn: () => {
            const params = limit !== undefined ? `?limit=${limit}` : ''
            return api.get<Task[]>(`/workspaces/${workspaceId}/tasks${params}`)
        },
    })

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

export const useUpdateTask = (workspaceId: string, projectId: string, taskId: string) =>
    useMutation({
        mutationFn: (data: {
            title?: string
            description?: string
            status?: TaskStatus
            assigneeId?: string
            removeAssignee?: boolean
        }) =>
            api.patch<TaskDetail>(
                `/workspaces/${workspaceId}/projects/${projectId}/tasks/${taskId}`,
                data,
            ),
    })

export const useDeleteTask = (workspaceId: string, projectId: string) =>
    useMutation({
        mutationFn: (taskId: string) =>
            api.delete(`/workspaces/${workspaceId}/projects/${projectId}/tasks/${taskId}`),
    })
