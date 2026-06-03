import { Button } from '@/components/ui/button'
import { DeleteProjectDialog } from '@/features/project/components/DeleteProjectDialog'
import { ProjectFormModal } from '@/features/project/components/ProjectFormModal'
import { useWorkspaceProjectsPage } from '@/features/project/hooks/useWorkspaceProjectsPage'
import type { Project } from '@/features/project/types'

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
    const {
        workspaceId,
        projects,
        isLoading,
        createOpen,
        setCreateOpen,
        editProject,
        setEditProject,
        deleteProject,
        setDeleteProject,
        canManageProject,
    } = useWorkspaceProjectsPage()

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-6 p-6">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-semibold">Projects</h1>
                <Button size="sm" onClick={() => setCreateOpen(true)}>
                    New project
                </Button>
            </div>
            {projects && projects.length > 0 ? (
                <ul className="flex flex-col gap-2">
                    {projects.map((project) => (
                        <li
                            key={project.id}
                            className="flex items-center justify-between rounded-lg border bg-card px-4 py-3"
                        >
                            <span className="font-medium">{project.name}</span>
                            <div className="flex items-center gap-2">
                                <span
                                    className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClass(project.status)}`}
                                >
                                    {statusLabel(project.status)}
                                </span>
                                {canManageProject(project) && (
                                    <>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() => setEditProject(project)}
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => setDeleteProject(project)}
                                        >
                                            Delete
                                        </Button>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">No projects yet.</p>
            )}

            <ProjectFormModal
                open={createOpen}
                onOpenChange={setCreateOpen}
                workspaceId={workspaceId}
            />

            {editProject && (
                <ProjectFormModal
                    open={!!editProject}
                    onOpenChange={(open) => !open && setEditProject(null)}
                    workspaceId={workspaceId}
                    project={editProject}
                />
            )}

            {deleteProject && (
                <DeleteProjectDialog
                    open={!!deleteProject}
                    onOpenChange={(open) => !open && setDeleteProject(null)}
                    workspaceId={workspaceId}
                    project={deleteProject}
                />
            )}
        </div>
    )
}
