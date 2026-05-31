import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useWorkspaceApi } from '@/lib/useWorkspaceApi'
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

export const useWorkspace = () => {
    const { workspaceId, get } = useWorkspaceApi()
    // get derives from workspaceId which is already in the key
    // eslint-disable-next-line @tanstack/query/exhaustive-deps
    return useQuery({
        queryKey: ['workspaces', workspaceId],
        queryFn: () => get<Workspace>(''),
    })
}

export const useRenameWorkspace = () => {
    const { patch } = useWorkspaceApi()

    return useMutation({
        mutationFn: (data: { name: string }) => patch<Workspace>('', data),
    })
}

export const useDeleteWorkspace = () => {
    const { delete: remove } = useWorkspaceApi()

    return useMutation({
        mutationFn: () => remove<null>(''),
    })
}
