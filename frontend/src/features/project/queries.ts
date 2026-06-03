import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import type { Project } from './types'

export const useProjects = (workspaceId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects'],
        queryFn: () => api.get<Project[]>(`/workspaces/${workspaceId}/projects`),
    })
