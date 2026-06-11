import { MyTasksTable } from '@/features/task/components/MyTasksTable'
import { StatsRow } from '@/features/task/components/StatsRow'
import { useWorkspaceMyTasks, useWorkspaceTaskStats } from '@/features/task/queries'
import { useIsMobile } from '@/hooks/useIsMobile'
import { ArrowRight } from 'lucide-react'
import { Link, useParams } from 'react-router'

export default function WorkspaceDashboardPage() {
    const { id } = useParams<{ id: string }>()
    const isMobile = useIsMobile()
    const { data: stats, isLoading: statsLoading } = useWorkspaceTaskStats(id!)
    const { data: myTasks, isLoading: tasksLoading } = useWorkspaceMyTasks(id!, 10)

    if (statsLoading || !stats) return null

    return (
        <div className="flex flex-col gap-4 p-4 md:gap-5 md:p-6">
            <StatsRow stats={stats} isMobile={isMobile} />
            {!tasksLoading && (
                <div className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold tracking-tight">My Tasks</h2>
                    <MyTasksTable
                        tasks={myTasks ?? []}
                        emptyMessage="No tasks assigned to you yet"
                    />
                    {(myTasks?.length ?? 0) > 0 && (
                        <Link
                            to={`/workspaces/${id}/my-tasks`}
                            className="text-primary flex items-center gap-1 self-end text-xs font-medium hover:underline"
                        >
                            View all
                            <ArrowRight className="size-3" />
                        </Link>
                    )}
                </div>
            )}
        </div>
    )
}
