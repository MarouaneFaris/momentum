import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { Workspace } from './types'

export const useWorkspaces = () =>
    useQuery({
        queryKey: ['workspaces'],
        queryFn: () => api.get<Workspace[]>(ROUTES.workspaces),
        staleTime: 5 * 60 * 1000,
    })

export const useCreateWorkspace = () => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (data: { name: string }) => api.post<Workspace>(ROUTES.workspaces, data),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
        },
    })
}

export const useWorkspace = (workspaceId: string) =>
    useQuery({
        queryKey: ['workspaces', workspaceId],
        queryFn: () => api.get<Workspace>(`${ROUTES.workspaces}/${workspaceId}`),
    })

export const useRenameWorkspace = (workspaceId: string) => {
    const queryClient = useQueryClient()
    return useMutation({
        mutationFn: (data: { name: string }) =>
            api.patch<Workspace>(`${ROUTES.workspaces}/${workspaceId}`, data),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
        },
    })
}
