import { Button } from '@/components/ui/button'
import { Pencil, Trash2, X } from 'lucide-react'
import type { TaskDetail } from '../types'
import { StatusBadge } from './StatusBadge'

function UserSummary({ user }: { user: { id: string; name: string } }) {
    const initials = user.name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="flex items-center gap-1.5">
            <div className="bg-primary/15 border-primary/30 text-primary flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[8px] font-semibold">
                {initials}
            </div>
            <span className="text-foreground text-[13px]">{user.name}</span>
        </div>
    )
}

function PanelRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="text-muted-foreground w-20 shrink-0 text-[11px] font-medium tracking-[0.05em] uppercase">
                {label}
            </span>
            <div className="text-foreground flex items-center gap-1.5 text-[13px]">{children}</div>
        </div>
    )
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

type Props = {
    task: TaskDetail | null
    onClose: () => void
    canEdit?: boolean
    onEdit?: () => void
    onDelete?: () => void
}

export default function TaskDetailPanel({ task, onClose, canEdit, onEdit, onDelete }: Props) {
    return (
        <div className="bg-card border-border flex h-full w-[28rem] min-w-[28rem] flex-col overflow-hidden border-l">
            {task ? (
                <>
                    <div className="border-border flex shrink-0 items-start gap-2 border-b px-5 py-5">
                        <p className="text-foreground flex-1 text-sm leading-snug font-semibold">
                            {task.title}
                        </p>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={onClose}
                            className="mt-0.5 h-6 w-6 shrink-0"
                        >
                            <X size={13} />
                        </Button>
                    </div>

                    <div className="flex flex-1 flex-col gap-3.5 overflow-y-auto px-5 py-4">
                        <PanelRow label="Status">
                            <StatusBadge status={task.status} />
                        </PanelRow>

                        <PanelRow label="Assignee">
                            {task.assignee ? (
                                <UserSummary user={task.assignee} />
                            ) : (
                                <span className="text-muted-foreground text-[13px]">—</span>
                            )}
                        </PanelRow>

                        <PanelRow label="Created by">
                            <UserSummary user={task.creator} />
                        </PanelRow>

                        <PanelRow label="Created">
                            <span className="text-muted-foreground text-[12px]">
                                {formatDate(task.createdAt)}
                            </span>
                        </PanelRow>

                        {task.description && (
                            <>
                                <div className="bg-border my-1 h-px" />
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-muted-foreground text-[11px] font-medium tracking-[0.05em] uppercase">
                                        Description
                                    </span>
                                    <p className="text-muted-foreground text-[13px] leading-relaxed whitespace-pre-wrap">
                                        {task.description}
                                    </p>
                                </div>
                            </>
                        )}
                    </div>

                    {canEdit && (
                        <div className="border-border flex shrink-0 items-center gap-2 border-t px-5 py-3">
                            {onDelete && (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={onDelete}
                                    className="text-destructive border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                >
                                    <Trash2 className="mr-1.5 size-3.5" />
                                    Delete
                                </Button>
                            )}
                            <div className="flex-1" />
                            {onEdit && (
                                <Button variant="outline" size="sm" onClick={onEdit}>
                                    <Pencil className="mr-1.5 size-3.5" />
                                    Edit
                                </Button>
                            )}
                        </div>
                    )}
                </>
            ) : null}
        </div>
    )
}
