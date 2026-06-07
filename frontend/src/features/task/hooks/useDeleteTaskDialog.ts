import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { toast } from 'sonner'
import { useDeleteTask } from '../queries'
import type { Task } from '../types'

export function useDeleteTaskDialog(
    workspaceId: string,
    projectId: string,
    onSuccess?: () => void,
) {
    const [taskToDelete, setTaskToDelete] = useState<Task | null>(null)
    const queryClient = useQueryClient()
    const mutation = useDeleteTask(workspaceId, projectId)

    const openDialog = (task: Task) => setTaskToDelete(task)
    const closeDialog = () => setTaskToDelete(null)

    const confirmDelete = () => {
        if (!taskToDelete) return
        const taskId = taskToDelete.id
        mutation.mutate(taskId, {
            onSuccess: () => {
                closeDialog()
                onSuccess?.()
                queryClient.removeQueries({
                    queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks', taskId],
                })
                void queryClient.invalidateQueries({
                    queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks'],
                    exact: true,
                })
            },
            onError: () => {
                toast.error('Failed to delete task.')
            },
        })
    }

    return {
        isOpen: taskToDelete !== null,
        taskToDelete,
        openDialog,
        closeDialog,
        confirmDelete,
        isPending: mutation.isPending,
    }
}
