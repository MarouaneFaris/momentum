import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation } from '@tanstack/react-query'
import type { Project } from './types'

export const useProjects = (workspaceId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects'],
        queryFn: () => api.get<Project[]>(`/workspaces/${workspaceId}/projects`),
    })

export const useCreateProject = (workspaceId: string) =>
    useMutation({
        mutationFn: (data: { name: string; description?: string; status?: string }) =>
            api.post<Project>(`/workspaces/${workspaceId}/projects`, data),
    })

export const useUpdateProject = (workspaceId: string, projectId: string) =>
    useMutation({
        mutationFn: (data: { name?: string; description?: string; status?: string }) =>
            api.patch<Project>(`/workspaces/${workspaceId}/projects/${projectId}`, data),
    })

export const useChangeProjectStatus = (workspaceId: string) =>
    useMutation({
        mutationFn: ({ projectId, status }: { projectId: string; status: string }) =>
            api.patch<Project>(`/workspaces/${workspaceId}/projects/${projectId}`, { status }),
    })

export const useDeleteProject = (workspaceId: string, projectId: string) =>
    useMutation({
        mutationFn: () => api.delete(`/workspaces/${workspaceId}/projects/${projectId}`),
    })
