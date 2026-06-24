import { EmptyState } from '@/components/EmptyState'
import { ClipboardList, AlertCircle } from 'lucide-react'
import { UserAvatar } from '@/components/UserAvatar'
import type { Task } from '../types'
import { StatusBadge } from './StatusBadge'

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
}

function MyTaskRow({ task }: { task: Task }) {
    const overdue = task.isOverdue && task.status !== 'done'

    return (
        <div
            className={`border-bg-card flex items-center gap-2.5 rounded-[var(--radius)] border px-3 py-2.5 ${overdue ? 'border-destructive/40 bg-destructive/5' : 'border-border bg-card'}`}
        >
            <span className="text-foreground min-w-0 flex-1 truncate text-sm leading-snug font-medium">
                {task.title}
            </span>
            {overdue && (
                <span className="text-destructive flex shrink-0 items-center gap-1 text-[11px] font-medium">
                    <AlertCircle size={11} />
                    {formatDate(task.dueDate!)}
                </span>
            )}
            <StatusBadge status={task.status} />
            {task.assignee && <UserAvatar name={task.assignee.name} size="sm" />}
        </div>
    )
}

type MyTasksTableProps = {
    tasks: Task[]
    emptyMessage?: string
}

export function MyTasksTable({
    tasks,
    emptyMessage = 'No tasks assigned to you',
}: MyTasksTableProps) {
    if (tasks.length === 0) {
        return <EmptyState icon={ClipboardList} title={emptyMessage} className="py-10" />
    }

    return (
        <div className="flex flex-col gap-1.5">
            {tasks.map((task) => (
                <MyTaskRow key={task.id} task={task} />
            ))}
        </div>
    )
}
