import { ClipboardList, Info, Plus } from 'lucide-react'
import { Link } from 'react-router'
import { EmptyState } from '@/components/EmptyState'
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import { Button } from '@/components/ui/button'
import type { Task, TaskStatus } from '../types'
import { TaskCard } from './TaskCard'

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

function NewTaskButton({ className, onClick }: { className?: string; onClick: () => void }) {
    return (
        <Button className={className} size="lg" onClick={onClick}>
            <Plus />
            New task
        </Button>
    )
}

export function TaskBoard({
    workspaceId,
    projectId,
    tasksByStatus,
    isEmpty,
    isGuest,
    isOwner,
    currentUserId,
    projectName,
    selectedTaskId,
    onOpen,
    onNewTask,
    onEdit,
    onDelete,
}: {
    workspaceId: string
    projectId: string
    tasksByStatus: Record<TaskStatus, Task[]>
    isEmpty: boolean
    isGuest: boolean
    isOwner: boolean
    currentUserId: string | undefined
    projectName: string | null
    selectedTaskId: string | null
    onOpen: (taskId: string) => void
    onNewTask: () => void
    onEdit: (task: Task) => void
    onDelete: (task: Task) => void
}) {
    return (
        <div className="flex flex-col gap-5">
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
                {!isGuest && <NewTaskButton onClick={onNewTask} />}
            </div>

            {isGuest && (
                <div className="bg-muted border-border text-muted-foreground flex items-center gap-2 rounded-[var(--radius)] border px-3 py-2 text-xs">
                    <Info className="size-3.5 shrink-0" />
                    You're viewing as a Guest — read-only access.
                </div>
            )}

            {isEmpty ? (
                <EmptyState
                    icon={ClipboardList}
                    title="No tasks yet"
                    description="Create a task to start tracking work for this project."
                    action={!isGuest && <NewTaskButton onClick={onNewTask} />}
                />
            ) : (
                <div className="grid grid-cols-3 items-start gap-3">
                    {COLUMNS.map(({ status, label }) => {
                        const tasks = tasksByStatus[status]
                        return (
                            <div key={status} className="flex flex-col gap-2">
                                <div className="flex items-center gap-2 px-1 pb-1">
                                    <span className="text-foreground text-xs font-semibold">
                                        {label}
                                    </span>
                                    <span className="text-muted-foreground bg-muted border-border rounded-full border px-1.5 text-[11px] leading-[18px]">
                                        {tasks.length}
                                    </span>
                                </div>
                                {tasks.length === 0 ? (
                                    <div className="border-border text-muted-foreground rounded-[var(--radius)] border border-dashed px-3 py-5 text-center text-xs">
                                        {COLUMN_EMPTY[status]}
                                    </div>
                                ) : (
                                    tasks.map((task) => (
                                        <TaskCard
                                            key={task.id}
                                            task={task}
                                            workspaceId={workspaceId}
                                            projectId={projectId}
                                            isGuest={isGuest}
                                            hasFullEditAccess={
                                                !isGuest &&
                                                (isOwner || task.creatorId === currentUserId)
                                            }
                                            isSelected={task.id === selectedTaskId}
                                            onOpen={onOpen}
                                            onEdit={onEdit}
                                            onDelete={onDelete}
                                        />
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
