import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { DeleteProjectDialog } from '@/features/project/components/DeleteProjectDialog'
import { ProjectFormModal } from '@/features/project/components/ProjectFormModal'
import { ProjectMembersDialog } from '@/features/project/components/ProjectMembersDialog'
import { useWorkspaceProjectsPage } from '@/features/project/hooks/useWorkspaceProjectsPage'
import type { Project, ProjectStatus } from '@/features/project/types'
import { EllipsisVerticalIcon, Plus } from 'lucide-react'
import { Link } from 'react-router'

const STATUS_TRANSITIONS: Record<ProjectStatus, { value: ProjectStatus; label: string }[]> = {
    draft: [
        { value: 'active', label: 'Set as Active' },
        { value: 'archived', label: 'Set as Archived' },
    ],
    active: [
        { value: 'draft', label: 'Set as Draft' },
        { value: 'archived', label: 'Set as Archived' },
    ],
    archived: [
        { value: 'draft', label: 'Set as Draft' },
        { value: 'active', label: 'Set as Active' },
    ],
}

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
        manageMembersProject,
        setManageMembersProject,
        canCreateProject,
        canManageProject,
        changeStatus,
        canManageMembers,
    } = useWorkspaceProjectsPage()

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-6 p-6">
            <div className="flex items-center justify-between">
                <h1 className="text-xl font-semibold">Projects</h1>
                {canCreateProject() && (
                    <Button size="lg" onClick={() => setCreateOpen(true)}>
                        <Plus />
                        New project
                    </Button>
                )}
            </div>
            {projects && projects.length > 0 ? (
                <ul className="flex flex-col gap-2">
                    {projects.map((project) => (
                        <li
                            key={project.id}
                            className="bg-card flex items-center justify-between rounded-lg border px-4 py-3"
                        >
                            <span className="font-medium">{project.name}</span>
                            <div className="flex items-center gap-2">
                                <Button variant="ghost" size="sm" asChild>
                                    <Link
                                        to={`/workspaces/${workspaceId}/projects/${project.id}/tasks`}
                                    >
                                        Tasks
                                    </Link>
                                </Button>
                                <span
                                    className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClass(project.status)}`}
                                >
                                    {statusLabel(project.status)}
                                </span>
                                <div className="size-9 shrink-0">
                                    {canManageProject(project) && (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon">
                                                    <EllipsisVerticalIcon className="size-4" />
                                                    <span className="sr-only">Project actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onClick={() => setEditProject(project)}
                                                >
                                                    Edit
                                                </DropdownMenuItem>
                                                {canManageMembers(project) && (
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <span>
                                                                <DropdownMenuItem
                                                                    disabled={
                                                                        project.status === 'draft'
                                                                    }
                                                                    onClick={() =>
                                                                        setManageMembersProject(
                                                                            project,
                                                                        )
                                                                    }
                                                                >
                                                                    Manage members
                                                                </DropdownMenuItem>
                                                            </span>
                                                        </TooltipTrigger>
                                                        {project.status === 'draft' && (
                                                            <TooltipContent side="left">
                                                                Guests can't be assigned to draft
                                                                projects
                                                            </TooltipContent>
                                                        )}
                                                    </Tooltip>
                                                )}
                                                <DropdownMenuSeparator />
                                                {STATUS_TRANSITIONS[project.status].map(
                                                    ({ value, label }) => (
                                                        <DropdownMenuItem
                                                            key={value}
                                                            onClick={() =>
                                                                changeStatus(project, value)
                                                            }
                                                        >
                                                            {label}
                                                        </DropdownMenuItem>
                                                    ),
                                                )}
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onClick={() => setDeleteProject(project)}
                                                >
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    )}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-muted-foreground text-sm">No projects yet.</p>
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

            {manageMembersProject && (
                <ProjectMembersDialog
                    open={!!manageMembersProject}
                    onOpenChange={(open) => !open && setManageMembersProject(null)}
                    workspaceId={workspaceId}
                    project={manageMembersProject}
                />
            )}
        </div>
    )
}
