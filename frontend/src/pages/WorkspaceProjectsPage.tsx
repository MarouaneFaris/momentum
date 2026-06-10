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
import { EllipsisVerticalIcon, FolderOpen, Plus } from 'lucide-react'
import { useState } from 'react'
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

function statusBadgeClass(status: Project['status']): string {
    return {
        draft: 'bg-muted text-muted-foreground border-border',
        active: 'bg-green-500/10 text-green-700 border-green-500/25 dark:text-green-400 dark:border-green-500/30',
        archived:
            'bg-amber-500/10 text-amber-700 border-amber-500/25 dark:text-amber-400 dark:border-amber-500/30',
    }[status]
}

const FILTERS = [
    { value: 'all', label: 'All' },
    { value: 'active', label: 'Active' },
    { value: 'draft', label: 'Draft' },
    { value: 'archived', label: 'Archived' },
] as const

type Filter = (typeof FILTERS)[number]['value']

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

    const [filter, setFilter] = useState<Filter>('all')

    if (isLoading) return null

    const filtered =
        filter === 'all' ? (projects ?? []) : (projects ?? []).filter((p) => p.status === filter)

    return (
        <div className="flex flex-col gap-5 p-6">
            <div className="flex items-center justify-between">
                <h1 className="text-base font-semibold tracking-tight">Projects</h1>
                {canCreateProject() && (
                    <Button size="sm" onClick={() => setCreateOpen(true)}>
                        <Plus className="size-3.5" />
                        New project
                    </Button>
                )}
            </div>

            <div className="flex items-center gap-2">
                {FILTERS.map(({ value, label }) => (
                    <button
                        key={value}
                        onClick={() => setFilter(value)}
                        className={`inline-flex h-7 cursor-pointer items-center gap-1 rounded-full border px-2.5 text-xs font-medium transition-colors ${
                            filter === value
                                ? 'border-primary/30 bg-primary/10 text-primary'
                                : 'border-border bg-muted text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        {label}
                    </button>
                ))}
            </div>

            {filtered.length > 0 ? (
                <div className="grid [grid-template-columns:repeat(auto-fill,minmax(240px,1fr))] gap-3">
                    {filtered.map((project) => (
                        <ProjectCard
                            key={project.id}
                            project={project}
                            workspaceId={workspaceId}
                            canManage={canManageProject(project)}
                            canManageMembers={canManageMembers(project)}
                            onEdit={() => setEditProject(project)}
                            onDelete={() => setDeleteProject(project)}
                            onManageMembers={() => setManageMembersProject(project)}
                            onChangeStatus={(status) => changeStatus(project, status)}
                        />
                    ))}
                    {canCreateProject() && (
                        <button
                            onClick={() => setCreateOpen(true)}
                            className="border-border text-muted-foreground hover:border-primary/40 hover:text-foreground flex min-h-[132px] cursor-pointer flex-col items-center justify-center gap-2 rounded-[calc(var(--radius))] border border-dashed transition-colors"
                        >
                            <Plus className="size-5" />
                            <span className="text-sm">New project</span>
                        </button>
                    )}
                </div>
            ) : projects && projects.length === 0 ? (
                <EmptyState onNew={canCreateProject() ? () => setCreateOpen(true) : undefined} />
            ) : (
                <p className="text-muted-foreground text-sm">No projects match this filter.</p>
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

type ProjectCardProps = {
    project: Project
    workspaceId: string
    canManage: boolean
    canManageMembers: boolean
    onEdit: () => void
    onDelete: () => void
    onManageMembers: () => void
    onChangeStatus: (status: ProjectStatus) => void
}

function ProjectCard({
    project,
    workspaceId,
    canManage,
    canManageMembers,
    onEdit,
    onDelete,
    onManageMembers,
    onChangeStatus,
}: ProjectCardProps) {
    return (
        <div className="bg-card border-border hover:border-primary/40 group relative flex flex-col gap-2.5 rounded-[calc(var(--radius))] border p-4 transition-[border-color,box-shadow] hover:shadow-[0_0_0_3px_hsl(var(--primary)/0.06)]">
            <Link
                to={`/workspaces/${workspaceId}/projects/${project.id}/tasks`}
                className="absolute inset-0 rounded-[calc(var(--radius))]"
                aria-label={project.name}
            />
            <div className="relative z-10 flex items-start gap-2">
                <span className="text-foreground min-w-0 flex-1 text-sm leading-snug font-medium">
                    {project.name}
                </span>
                <span
                    className={`inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-medium whitespace-nowrap ${statusBadgeClass(project.status)}`}
                >
                    {statusLabel(project.status)}
                </span>
                {canManage && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-foreground size-6 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
                                onClick={(e) => e.preventDefault()}
                            >
                                <EllipsisVerticalIcon className="size-3.5" />
                                <span className="sr-only">Project actions</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={onEdit}>Edit</DropdownMenuItem>
                            {canManageMembers && (
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <span>
                                            <DropdownMenuItem
                                                disabled={project.status === 'draft'}
                                                onClick={onManageMembers}
                                            >
                                                Manage members
                                            </DropdownMenuItem>
                                        </span>
                                    </TooltipTrigger>
                                    {project.status === 'draft' && (
                                        <TooltipContent side="left">
                                            Guests can't be assigned to draft projects
                                        </TooltipContent>
                                    )}
                                </Tooltip>
                            )}
                            <DropdownMenuSeparator />
                            {STATUS_TRANSITIONS[project.status].map(({ value, label }) => (
                                <DropdownMenuItem key={value} onClick={() => onChangeStatus(value)}>
                                    {label}
                                </DropdownMenuItem>
                            ))}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem variant="destructive" onClick={onDelete}>
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
            {project.description && (
                <p className="text-muted-foreground relative z-10 line-clamp-2 text-xs leading-relaxed">
                    {project.description}
                </p>
            )}
        </div>
    )
}

function EmptyState({ onNew }: { onNew?: () => void }) {
    return (
        <div className="flex flex-col items-center gap-4 py-16">
            <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                <FolderOpen className="text-muted-foreground size-5" />
            </div>
            <div className="text-center">
                <p className="text-foreground text-sm font-medium">No projects yet</p>
                <p className="text-muted-foreground mt-1 text-xs">
                    Create your first project to get started.
                </p>
            </div>
            {onNew && (
                <Button size="sm" onClick={onNew}>
                    <Plus className="size-3.5" />
                    New project
                </Button>
            )}
        </div>
    )
}
