import { PageHeader } from '@/components/PageHeader'
import { useMyTasksPage } from '../hooks/useMyTasksPage'
import { MyTasksTable } from './MyTasksTable'

export default function MyTasksPage() {
    const { tasks, isLoading } = useMyTasksPage()

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-5 p-4 md:p-6">
            <PageHeader title="My Tasks" />
            <MyTasksTable tasks={tasks} emptyMessage="No tasks assigned to you" />
        </div>
    )
}
