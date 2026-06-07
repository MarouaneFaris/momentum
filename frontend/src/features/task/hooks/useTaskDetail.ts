import { useState } from 'react'
import { useTask } from '../queries'

export function useTaskDetail(workspaceId: string, projectId: string) {
    const [selectedTaskId, setSelectedTaskId] = useState<string | null>(null)
    const { data: task, isLoading } = useTask(workspaceId, projectId, selectedTaskId)

    return {
        selectedTaskId,
        task: task ?? null,
        isLoading,
        open: (taskId: string) => setSelectedTaskId(taskId),
        close: () => setSelectedTaskId(null),
    }
}
