import { PageHeader } from '@/components/PageHeader'
import { getProjectColor } from '@/features/project/projectColor'
import { useProjects } from '@/features/project/queries'
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
    const { data: projects } = useProjects(id!)
    const sortedProjects = [...(projects ?? [])].sort(
        (a, b) => new Date(a.createdAt).getTime() - new Date(b.createdAt).getTime(),
    )

    if (statsLoading || !stats) return null

    return (
        <div className="flex flex-col gap-4 p-4 md:gap-5 md:p-6">
            <PageHeader title="Dashboard" />
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
            {isMobile && sortedProjects.length > 0 && (
                <div className="flex flex-col gap-2 md:hidden">
                    <h2 className="text-sm font-semibold tracking-tight">Projects</h2>
                    <div className="bg-card divide-border divide-y overflow-hidden rounded-[var(--radius)] border">
                        {sortedProjects.map((project) => (
                            <Link
                                key={project.id}
                                to={`/workspaces/${id}/projects/${project.id}/tasks`}
                                className="hover:bg-muted flex items-center gap-2 px-3 py-2.5"
                            >
                                <span
                                    className="h-1.5 w-1.5 shrink-0 rounded-full"
                                    style={{ background: getProjectColor(project.id) }}
                                />
                                <span className="text-foreground text-xs font-medium">
                                    {project.name}
                                </span>
                            </Link>
                        ))}
                    </div>
                </div>
            )}
        </div>
    )
}
