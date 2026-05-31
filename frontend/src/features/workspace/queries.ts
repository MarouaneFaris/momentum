import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useMutation, useQuery } from '@tanstack/react-query'
import type { Workspace } from './types'

export const useWorkspaces = () =>
    useQuery({
        queryKey: ['workspaces'],
        queryFn: () => api.get<Workspace[]>(ROUTES.workspaces),
        staleTime: 5 * 60 * 1000,
    })

export const useCreateWorkspace = () =>
    useMutation({
        mutationFn: (data: { name: string }) => api.post<Workspace>(ROUTES.workspaces, data),
    })

export const useWorkspace = (workspaceId: string) =>
    useQuery({
        queryKey: ['workspaces', workspaceId],
        queryFn: () => api.get<Workspace>(`${ROUTES.workspaces}/${workspaceId}`),
    })

export const useRenameWorkspace = (workspaceId: string) =>
    useMutation({
        mutationFn: (data: { name: string }) =>
            api.patch<Workspace>(`${ROUTES.workspaces}/${workspaceId}`, data),
    })

export const useDeleteWorkspace = (workspaceId: string) =>
    useMutation({
        mutationFn: () => api.delete<null>(`${ROUTES.workspaces}/${workspaceId}`),
    })
