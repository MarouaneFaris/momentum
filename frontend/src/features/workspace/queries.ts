import api from '@/lib/api'
import { ROUTES } from '@/lib/routes'
import { useQuery } from '@tanstack/react-query'
import type { Workspace } from './types'

export const useWorkspaces = () =>
    useQuery({
        queryKey: ['workspaces'],
        queryFn: () => api.get<Workspace[]>(ROUTES.workspaces),
    })
