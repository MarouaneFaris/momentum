import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import type { Project, ProjectStatus } from '@/features/project/types'
import { EllipsisIcon } from 'lucide-react'
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

export type ProjectCardProps = {
    project: Project
    workspaceId: string
    canManage: boolean
    canManageMembers: boolean
    onEdit: () => void
    onDelete: () => void
    onManageMembers: () => void
    onChangeStatus: (status: ProjectStatus) => void
}

export function ProjectCard({
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
        <div className="bg-card border-border hover:border-primary/40 group relative flex cursor-pointer flex-col gap-2.5 rounded-[calc(var(--radius))] border p-4 transition-[border-color,box-shadow] hover:shadow-[0_0_0_3px_hsl(var(--primary)/0.06)]">
            <Link
                to={`/workspaces/${workspaceId}/projects/${project.id}/tasks`}
                className="absolute inset-0 rounded-[calc(var(--radius))]"
                aria-label={project.name}
            />

            {canManage && (
                <div className="absolute top-0 right-0 z-20">
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-muted-foreground hover:text-foreground size-6 shrink-0 cursor-pointer opacity-0 transition-opacity group-hover:opacity-100"
                                onClick={(e) => e.preventDefault()}
                            >
                                <EllipsisIcon className="size-3.5" />
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
                </div>
            )}

            <div className="relative z-10 flex items-start gap-2">
                <span className="text-foreground min-w-0 flex-1 text-sm leading-snug font-medium">
                    {project.name}
                </span>
                <span
                    className={`inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-medium whitespace-nowrap ${statusBadgeClass(project.status)}`}
                >
                    {statusLabel(project.status)}
                </span>
            </div>

            {project.description && (
                <p className="text-muted-foreground relative z-10 line-clamp-2 text-xs leading-relaxed">
                    {project.description}
                </p>
            )}
        </div>
    )
}
