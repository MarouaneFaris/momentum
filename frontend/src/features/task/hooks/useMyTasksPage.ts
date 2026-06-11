import { useParams } from 'react-router'
import { useWorkspaceMyTasks } from '../queries'

export function useMyTasksPage() {
    const { id: workspaceId } = useParams<{ id: string }>()
    const { data: tasks, isLoading } = useWorkspaceMyTasks(workspaceId!)

    return {
        workspaceId: workspaceId!,
        tasks: tasks ?? [],
        isLoading,
    }
}
