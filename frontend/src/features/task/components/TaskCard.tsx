import { Pencil, Trash2 } from 'lucide-react'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useUpdateTaskStatus } from '../hooks/useUpdateTaskStatus'
import type { Task } from '../types'
import { STATUS_OPTIONS } from '../taskStatus'
import { StatusBadge } from './StatusBadge'

function Assignee({ assignee }: { assignee: Task['assignee'] }) {
    if (!assignee) {
        return <span className="text-muted-foreground text-[11px]">—</span>
    }

    const initials = assignee.name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="flex items-center gap-1">
            <div className="bg-primary/15 border-primary/30 text-primary flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[8px] font-semibold">
                {initials}
            </div>
            <span className="text-muted-foreground text-[11px]">{assignee.name.split(' ')[0]}</span>
        </div>
    )
}

export function TaskCard({
    task,
    workspaceId,
    projectId,
    isGuest,
    hasFullEditAccess,
    isSelected,
    onOpen,
    onEdit,
    onDelete,
}: {
    task: Task
    workspaceId: string
    projectId: string
    isGuest: boolean
    hasFullEditAccess: boolean
    isSelected: boolean
    onOpen: (taskId: string) => void
    onEdit: (task: Task) => void
    onDelete: (task: Task) => void
}) {
    const { update: handleStatusChange } = useUpdateTaskStatus(workspaceId, projectId, task.id)

    return (
        <div
            className={[
                'bg-card flex cursor-pointer flex-col gap-2 rounded-[var(--radius)] border p-3 transition-[border-color,box-shadow] duration-150',
                isSelected
                    ? 'border-primary [box-shadow:0_0_0_3px_oklch(0.488_0.243_264.376_/_0.1)]'
                    : 'border-border hover:border-primary/40 hover:[box-shadow:0_0_0_3px_oklch(0.488_0.243_264.376_/_0.06)]',
            ].join(' ')}
            onClick={() => onOpen(task.id)}
        >
            <div className="text-foreground text-[13px] leading-snug font-medium">{task.title}</div>
            <div className="flex items-center gap-1.5">
                <StatusBadge status={task.status} />
                <div className="flex-1" />
                <Assignee assignee={task.assignee} />
                {!isGuest && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <div
                                className="text-muted-foreground hover:bg-muted flex h-5 w-5 cursor-pointer items-center justify-center rounded text-[13px]"
                                onClick={(e) => e.stopPropagation()}
                            >
                                ⋯
                            </div>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" onClick={(e) => e.stopPropagation()}>
                            <DropdownMenuLabel className="text-xs">Change status</DropdownMenuLabel>
                            {STATUS_OPTIONS.filter((o) => o.value !== task.status).map((o) => (
                                <DropdownMenuItem
                                    key={o.value}
                                    onClick={() => handleStatusChange(o.value)}
                                >
                                    {o.label}
                                </DropdownMenuItem>
                            ))}
                            {hasFullEditAccess && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onClick={() => onEdit(task)}>
                                        <Pencil className="mr-1.5 size-3.5" />
                                        Edit task
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onClick={() => onDelete(task)}
                                        className="text-destructive focus:text-destructive"
                                    >
                                        <Trash2 className="mr-1.5 size-3.5" />
                                        Delete task
                                    </DropdownMenuItem>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </div>
    )
}
