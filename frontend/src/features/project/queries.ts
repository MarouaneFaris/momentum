import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import type { Project, ProjectMember } from './types'

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

export const useProjectMembers = (workspaceId: string, projectId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'members'],
        queryFn: () =>
            api.get<ProjectMember[]>(`/workspaces/${workspaceId}/projects/${projectId}/members`),
    })

export const useAssignProjectMember = (workspaceId: string, projectId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (data: { userId: string }) =>
            api.post<ProjectMember>(
                `/workspaces/${workspaceId}/projects/${projectId}/members`,
                data,
            ),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'projects', projectId, 'members'],
            })
        },
    })
}

export const useRemoveProjectMember = (workspaceId: string, projectId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (userId: string) =>
            api.delete(`/workspaces/${workspaceId}/projects/${projectId}/members/${userId}`),
        onSuccess: () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'projects', projectId, 'members'],
            })
        },
    })
}
