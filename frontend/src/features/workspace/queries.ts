import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import queryClient from '@/lib/queryClient'
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
        mutationFn: (data: { name: string }) =>
            api.post<Workspace>(ROUTES.workspaces, data),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
        },
    })
