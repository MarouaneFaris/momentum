import { useParams } from 'react-router'
import { useWorkspaceMyTasks } from '../queries'
import { MyTasksTable } from './MyTasksTable'

export default function MyTasksPage() {
    const { id: workspaceId } = useParams<{ id: string }>()
    const { data: tasks, isLoading } = useWorkspaceMyTasks(workspaceId!)

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-5">
            <div className="flex items-center gap-3">
                <h1 className="text-base font-semibold tracking-tight">My Tasks</h1>
            </div>
            <MyTasksTable tasks={tasks ?? []} emptyMessage="No tasks assigned to you" />
        </div>
    )
}
