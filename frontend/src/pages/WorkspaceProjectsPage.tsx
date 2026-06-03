import { useProjects } from '@/features/project/queries'
import type { Project } from '@/features/project/types'
import { useParams } from 'react-router'

function statusLabel(status: Project['status']): string {
    return { draft: 'Draft', active: 'Active', archived: 'Archived' }[status]
}

function statusClass(status: Project['status']): string {
    return {
        draft: 'bg-muted text-muted-foreground',
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        archived: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    }[status]
}

export default function WorkspaceProjectsPage() {
    const { id } = useParams<{ id: string }>()
    const { data: projects, isLoading } = useProjects(id!)

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-6 p-6">
            <h1 className="text-xl font-semibold">Projects</h1>
            {projects && projects.length > 0 ? (
                <ul className="flex flex-col gap-2">
                    {projects.map((project) => (
                        <li
                            key={project.id}
                            className="flex items-center justify-between rounded-lg border bg-card px-4 py-3"
                        >
                            <span className="font-medium">{project.name}</span>
                            <span
                                className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClass(project.status)}`}
                            >
                                {statusLabel(project.status)}
                            </span>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">No projects yet.</p>
            )}
        </div>
    )
}
