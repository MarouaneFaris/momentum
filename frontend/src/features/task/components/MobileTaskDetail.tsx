import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useUpdateTaskStatus } from '../hooks/useUpdateTaskStatus'
import type { TaskDetail } from '../types'
import { STATUS_OPTIONS } from '../taskStatus'
import { ArrowLeft, MoreHorizontal, Pencil, Trash2 } from 'lucide-react'
import { DetailRow } from './DetailRow'
import { UserAvatar } from '@/components/UserAvatar'
import { StatusBadge } from './StatusBadge'

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

export function MobileTaskDetail({
    task,
    projectName,
    workspaceId,
    projectId,
    isGuest,
    canEdit,
    onBack,
    onEdit,
    onDelete,
}: {
    task: TaskDetail
    projectName: string | null
    workspaceId: string
    projectId: string
    isGuest: boolean
    canEdit: boolean
    onBack: () => void
    onEdit: () => void
    onDelete: () => void
}) {
    const { update: changeStatus } = useUpdateTaskStatus(workspaceId, projectId, task.id)

    return (
        <div className="flex flex-col">
            <div className="bg-background border-border sticky top-0 z-10 flex items-center gap-2 border-b px-4 py-3">
                <Button
                    variant="ghost"
                    size="icon"
                    onClick={onBack}
                    aria-label="Back"
                    className="-ml-1 h-8 w-8 shrink-0"
                >
                    <ArrowLeft size={18} />
                </Button>
                <span className="text-foreground flex-1 truncate text-sm font-semibold">
                    {task.title}
                </span>
                {canEdit && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label="Task actions"
                                className="h-8 w-8 shrink-0"
                            >
                                <MoreHorizontal size={18} />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={onEdit}>
                                <Pencil className="mr-1.5 size-3.5" />
                                Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                onClick={onDelete}
                                className="text-destructive focus:text-destructive"
                            >
                                <Trash2 className="mr-1.5 size-3.5" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>

            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-foreground text-lg leading-snug font-semibold">{task.title}</h1>

                <div className="border-border bg-card flex flex-col gap-2.5 rounded-[var(--radius)] border p-3">
                    <DetailRow label="Status">
                        <StatusBadge status={task.status} />
                    </DetailRow>
                    <DetailRow label="Assignee">
                        {task.assignee ? (
                            <div className="flex items-center gap-1.5">
                                <UserAvatar name={task.assignee.name} size="sm" />
                                <span className="text-foreground text-[13px]">
                                    {task.assignee.name}
                                </span>
                            </div>
                        ) : (
                            <span className="text-muted-foreground text-[13px]">—</span>
                        )}
                    </DetailRow>
                    <DetailRow label="Creator">
                        <div className="flex items-center gap-1.5">
                            <UserAvatar name={task.creator.name} size="sm" />
                            <span className="text-foreground text-[13px]">{task.creator.name}</span>
                        </div>
                    </DetailRow>
                    {projectName && (
                        <DetailRow label="Project">
                            <span className="text-foreground text-[13px]">{projectName}</span>
                        </DetailRow>
                    )}
                    <DetailRow label="Created">
                        <span className="text-muted-foreground text-[12px]">
                            {formatDate(task.createdAt)}
                        </span>
                    </DetailRow>
                </div>

                {task.description && (
                    <div className="border-border bg-card rounded-[var(--radius)] border p-3">
                        <p className="text-muted-foreground mb-2 text-[11px] font-medium tracking-[0.05em] uppercase">
                            Description
                        </p>
                        <p className="text-muted-foreground text-[13px] leading-relaxed whitespace-pre-wrap">
                            {task.description}
                        </p>
                    </div>
                )}

                {!isGuest && (
                    <div className="flex flex-wrap gap-2">
                        {STATUS_OPTIONS.filter((o) => o.value !== task.status).map((o) => (
                            <Button
                                key={o.value}
                                variant="outline"
                                size="sm"
                                onClick={() => changeStatus(o.value)}
                            >
                                {o.label}
                            </Button>
                        ))}
                        {canEdit && (
                            <>
                                <Button variant="outline" size="sm" onClick={onEdit}>
                                    <Pencil className="mr-1.5 size-3.5" />
                                    Edit
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={onDelete}
                                    className="text-destructive border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                >
                                    <Trash2 className="mr-1.5 size-3.5" />
                                    Delete
                                </Button>
                            </>
                        )}
                    </div>
                )}
            </div>
        </div>
    )
}
