import { useQueryClient } from '@tanstack/react-query'
import { useUpdateTask } from '../queries'
import type { TaskStatus } from '../types'

export function useUpdateTaskStatus(workspaceId: string, projectId: string, taskId: string) {
    const queryClient = useQueryClient()
    const mutation = useUpdateTask(workspaceId, projectId, taskId)

    const update = (status: TaskStatus) => {
        mutation.mutate(
            { status },
            {
                onSuccess: () => {
                    void queryClient.invalidateQueries({
                        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks'],
                    })
                },
            },
        )
    }

    return { update, isPending: mutation.isPending }
}
