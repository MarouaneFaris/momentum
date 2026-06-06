import api from '@/lib/api'
import { useSettledQuery } from '@/lib/useSettledQuery'
import type { Task } from './types'

export const useWorkspaceProjectTasks = (workspaceId: string, projectId: string) =>
    useSettledQuery({
        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks'],
        queryFn: () => api.get<Task[]>(`/workspaces/${workspaceId}/projects/${projectId}/tasks`),
    })
