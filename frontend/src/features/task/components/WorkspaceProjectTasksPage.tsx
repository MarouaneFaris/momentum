import { ClipboardList, Info, Plus } from 'lucide-react'
import { Link } from 'react-router'

import { Button } from '@/components/ui/button'
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'

import { useWorkspaceProjectTasksPage } from '../hooks/useWorkspaceProjectTasksPage'
import type { Task, TaskStatus } from '../types'

const COLUMNS: { status: TaskStatus; label: string }[] = [
    { status: 'todo', label: 'Todo' },
    { status: 'in-progress', label: 'In Progress' },
    { status: 'done', label: 'Done' },
]

const COLUMN_EMPTY: Record<TaskStatus, string> = {
    todo: 'No tasks yet.',
    'in-progress': 'Nothing in progress yet.',
    done: 'No completed tasks yet.',
}

function StatusBadge({ status }: { status: TaskStatus }) {
    const classes = {
        todo: 'bg-muted text-muted-foreground border border-border',
        'in-progress': 'bg-primary/10 text-primary border border-primary/25',
        done: 'bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
    }[status]

    const label = { todo: 'Todo', 'in-progress': 'In progress', done: 'Done' }[status]

    return (
        <span
            className={`inline-flex items-center text-[10px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap ${classes}`}
        >
            {label}
        </span>
    )
}

function Assignee({ assignee }: { assignee: Task['assignee'] }) {
    if (!assignee) {
        return <span className="text-[11px] text-muted-foreground">—</span>
    }

    const initials = assignee.name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="flex items-center gap-1">
            <div className="w-5 h-5 rounded-full bg-primary/15 border border-primary/30 flex items-center justify-center text-[8px] font-semibold text-primary flex-shrink-0">
                {initials}
            </div>
            <span className="text-[11px] text-muted-foreground">{assignee.name.split(' ')[0]}</span>
        </div>
    )
}

function TaskCard({ task, isGuest }: { task: Task; isGuest: boolean }) {
    return (
        <div
            className={[
                'bg-card border border-border rounded-[var(--radius)] p-3 flex flex-col gap-2 transition-[border-color,box-shadow] duration-150',
                isGuest
                    ? 'cursor-default'
                    : 'cursor-pointer hover:border-primary/40 hover:[box-shadow:0_0_0_3px_oklch(0.488_0.243_264.376_/_0.06)]',
            ].join(' ')}
        >
            <div className="text-[13px] font-medium text-foreground leading-snug">{task.title}</div>
            <div className="flex items-center gap-1.5">
                <StatusBadge status={task.status} />
                <div className="flex-1" />
                <Assignee assignee={task.assignee} />
                {!isGuest && (
                    <div className="w-5 h-5 flex items-center justify-center rounded text-muted-foreground text-[13px] hover:bg-muted cursor-pointer">
                        ⋯
                    </div>
                )}
            </div>
        </div>
    )
}

export default function WorkspaceProjectTasksPage() {
    const { workspaceId, isLoading, tasksByStatus, isEmpty, isGuest, projectName } =
        useWorkspaceProjectTasksPage()

    if (isLoading) return null

    return (
        <div className="flex flex-col gap-5 p-6">
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link to={`/workspaces/${workspaceId}/projects`}>Projects</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    {projectName && (
                        <>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>{projectName}</BreadcrumbPage>
                            </BreadcrumbItem>
                        </>
                    )}
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Tasks</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>
            <div className="flex items-center gap-3">
                <h1 className="text-base font-semibold tracking-tight">Tasks</h1>
                <div className="flex-1" />
                {!isGuest && (
                    <Button size="sm">
                        <Plus />
                        New task
                    </Button>
                )}
            </div>

            {isGuest && (
                <div className="flex items-center gap-2 px-3 py-2 bg-muted border border-border rounded-[var(--radius)] text-xs text-muted-foreground">
                    <Info className="size-3.5 shrink-0" />
                    You're viewing as a Guest — read-only access.
                </div>
            )}

            {isEmpty ? (
                <div className="flex flex-col items-center gap-3 py-16 px-6 text-center">
                    <ClipboardList className="size-10 text-border" />
                    <div className="text-sm font-medium text-foreground">No tasks yet</div>
                    <div className="text-[13px] text-muted-foreground">
                        Create a task to start tracking work for this project.
                    </div>
                    {!isGuest && (
                        <Button size="sm" className="mt-1">
                            <Plus />
                            New task
                        </Button>
                    )}
                </div>
            ) : (
                <div className="grid grid-cols-3 gap-3 items-start">
                    {COLUMNS.map(({ status, label }) => {
                        const tasks = tasksByStatus[status]
                        return (
                            <div key={status} className="flex flex-col gap-2">
                                <div className="flex items-center gap-2 px-1 pb-1">
                                    <span className="text-xs font-semibold text-foreground">
                                        {label}
                                    </span>
                                    <span className="text-[11px] text-muted-foreground bg-muted border border-border rounded-full px-1.5 leading-[18px]">
                                        {tasks.length}
                                    </span>
                                </div>
                                {tasks.length === 0 ? (
                                    <div className="border border-dashed border-border rounded-[var(--radius)] px-3 py-5 text-center text-xs text-muted-foreground">
                                        {COLUMN_EMPTY[status]}
                                    </div>
                                ) : (
                                    tasks.map((task) => (
                                        <TaskCard key={task.id} task={task} isGuest={isGuest} />
                                    ))
                                )}
                            </div>
                        )
                    })}
                </div>
            )}
        </div>
    )
}
