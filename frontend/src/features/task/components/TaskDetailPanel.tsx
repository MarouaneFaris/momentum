import { Badge } from '@/components/ui/badge'
import { X } from 'lucide-react'
import type { TaskDetail, TaskStatus } from '../types'

function StatusBadge({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'bg-muted text-muted-foreground border-border',
        'in-progress': 'bg-primary/10 text-primary border-primary/25',
        done: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
    }[status]

    const label = { todo: 'Todo', 'in-progress': 'In progress', done: 'Done' }[status]

    return (
        <Badge variant="outline" className={className}>
            {label}
        </Badge>
    )
}

function UserSummary({ user }: { user: { id: string; name: string } }) {
    const initials = user.name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="flex items-center gap-1.5">
            <div className="w-5 h-5 rounded-full bg-primary/15 border border-primary/30 flex items-center justify-center text-[8px] font-semibold text-primary flex-shrink-0">
                {initials}
            </div>
            <span className="text-[13px] text-foreground">{user.name}</span>
        </div>
    )
}

function PanelRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center gap-2.5">
            <span className="text-[11px] font-medium text-muted-foreground uppercase tracking-[0.05em] w-20 flex-shrink-0">
                {label}
            </span>
            <div className="flex items-center gap-1.5 text-[13px] text-foreground">{children}</div>
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
}

export default function TaskDetailPanel({ task, onClose }: Props) {
    return (
        <div className="w-[28rem] min-w-[28rem] flex flex-col bg-card border-l border-border overflow-hidden h-full">
            {task ? (
                <>
                    <div className="flex items-start gap-2 px-5 py-5 border-b border-border flex-shrink-0">
                        <p className="flex-1 text-sm font-semibold text-foreground leading-snug">
                            {task.title}
                        </p>
                        <button
                            onClick={onClose}
                            className="w-6 h-6 flex items-center justify-center rounded hover:bg-muted text-muted-foreground flex-shrink-0 mt-0.5"
                        >
                            <X size={13} />
                        </button>
                    </div>

                    <div className="flex-1 flex flex-col gap-3.5 px-5 py-4 overflow-y-auto">
                        <PanelRow label="Status">
                            <StatusBadge status={task.status} />
                        </PanelRow>

                        <PanelRow label="Assignee">
                            {task.assignee ? (
                                <UserSummary user={task.assignee} />
                            ) : (
                                <span className="text-[13px] text-muted-foreground">—</span>
                            )}
                        </PanelRow>

                        <PanelRow label="Created by">
                            <UserSummary user={task.creator} />
                        </PanelRow>

                        <PanelRow label="Created">
                            <span className="text-[12px] text-muted-foreground">
                                {formatDate(task.createdAt)}
                            </span>
                        </PanelRow>

                        {task.description && (
                            <>
                                <div className="h-px bg-border my-1" />
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-medium text-muted-foreground uppercase tracking-[0.05em]">
                                        Description
                                    </span>
                                    <p className="text-[13px] text-muted-foreground leading-relaxed whitespace-pre-wrap">
                                        {task.description}
                                    </p>
                                </div>
                            </>
                        )}
                    </div>
                </>
            ) : (
                <div className="flex-1 flex items-center justify-center">
                    <span className="text-xs text-muted-foreground">Loading…</span>
                </div>
            )}
        </div>
    )
}
