import { useAuth } from '@/features/auth/queries'
import { useIsMobile } from '@/hooks/useIsMobile'
import { ClipboardList, Info, Pencil, Plus, Trash2 } from 'lucide-react'
import { Link } from 'react-router'

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import { useCreateTaskModal } from '../hooks/useCreateTaskModal'
import { useDeleteTaskDialog } from '../hooks/useDeleteTaskDialog'
import { useEditTaskModal } from '../hooks/useEditTaskModal'
import { useTaskDetail } from '../hooks/useTaskDetail'
import { useUpdateTaskStatus } from '../hooks/useUpdateTaskStatus'
import { useWorkspaceProjectTasksPage } from '../hooks/useWorkspaceProjectTasksPage'
import type { Task, TaskStatus } from '../types'
import { STATUS_OPTIONS } from '../taskStatus'
import { DeleteTaskDialog } from './DeleteTaskDialog'
import { MobileTasksView } from './MobileTasksView'
import { StatusBadge } from './StatusBadge'
import TaskDetailPanel from './TaskDetailPanel'
import { TaskFormModal } from './TaskFormModal'

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

function TaskCard({
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

function TaskBoard({
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
                <div className="flex flex-col items-center gap-3 px-6 py-16 text-center">
                    <ClipboardList className="text-border size-10" />
                    <div className="text-foreground text-sm font-medium">No tasks yet</div>
                    <div className="text-muted-foreground text-[13px]">
                        Create a task to start tracking work for this project.
                    </div>
                    {!isGuest && <NewTaskButton className="mt-1" onClick={onNewTask} />}
                </div>
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

export default function WorkspaceProjectTasksPage() {
    const {
        workspaceId,
        projectId,
        isLoading,
        tasksByStatus,
        isEmpty,
        isGuest,
        isOwner,
        projectName,
        tasks,
    } = useWorkspaceProjectTasksPage()
    const { data: authUser } = useAuth()
    const isMobile = useIsMobile()
    const detail = useTaskDetail(workspaceId, projectId)
    const modal = useCreateTaskModal(workspaceId, projectId)
    const editModal = useEditTaskModal(workspaceId, projectId)
    const deleteDialog = useDeleteTaskDialog(workspaceId, projectId, detail.close)

    if (isLoading) return null

    if (isMobile) {
        return (
            <MobileTasksView
                workspaceId={workspaceId}
                projectId={projectId}
                tasks={tasks}
                isEmpty={isEmpty}
                isGuest={isGuest}
                isOwner={isOwner}
                projectName={projectName}
            />
        )
    }

    const panelOpen = detail.selectedTaskId !== null

    const board = (
        <TaskBoard
            workspaceId={workspaceId}
            projectId={projectId}
            tasksByStatus={tasksByStatus}
            isEmpty={isEmpty}
            isGuest={isGuest}
            isOwner={isOwner}
            currentUserId={authUser?.id}
            projectName={projectName}
            selectedTaskId={detail.selectedTaskId}
            onOpen={detail.open}
            onNewTask={() => modal.setOpen(true)}
            onEdit={editModal.open}
            onDelete={deleteDialog.openDialog}
        />
    )

    return (
        <>
            <div className="-m-8 flex overflow-hidden" style={{ height: 'calc(100% + 4rem)' }}>
                <div
                    className={[
                        'flex min-w-0 flex-1 flex-col gap-5 overflow-y-auto p-6 transition-[border-color] duration-200',
                        panelOpen ? 'border-border border-r' : 'border-r border-transparent',
                    ].join(' ')}
                >
                    {board}
                </div>
                <div
                    className={[
                        'shrink-0 overflow-hidden transition-[width] duration-200 ease-in-out',
                        panelOpen ? 'w-[28rem]' : 'w-0',
                    ].join(' ')}
                >
                    <TaskDetailPanel
                        task={detail.task}
                        onClose={detail.close}
                        canEdit={
                            !isGuest &&
                            detail.task !== null &&
                            (isOwner || detail.task.creator.id === authUser?.id)
                        }
                        onEdit={() => {
                            if (detail.task) editModal.openFromDetail(detail.task)
                        }}
                        onDelete={() => {
                            if (detail.task) {
                                deleteDialog.openDialog({
                                    id: detail.task.id,
                                    title: detail.task.title,
                                    status: detail.task.status,
                                    assignee: detail.task.assignee,
                                    createdAt: detail.task.createdAt,
                                    creatorId: detail.task.creator.id,
                                })
                            }
                        }}
                    />
                </div>
            </div>
            <TaskFormModal
                open={modal.open}
                onOpenChange={modal.setOpen}
                workspaceId={workspaceId}
                form={modal.form}
                isPending={modal.isPending}
                onSubmit={modal.onSubmit}
            />
            <TaskFormModal
                open={editModal.isOpen}
                onOpenChange={(v) => {
                    if (!v) editModal.close()
                }}
                workspaceId={workspaceId}
                form={editModal.form}
                isPending={editModal.isPending}
                onSubmit={editModal.onSubmit}
                mode="edit"
            />
            <DeleteTaskDialog
                open={deleteDialog.isOpen}
                onOpenChange={(v) => {
                    if (!v) deleteDialog.closeDialog()
                }}
                task={deleteDialog.taskToDelete}
                isPending={deleteDialog.isPending}
                onConfirm={deleteDialog.confirmDelete}
            />
        </>
    )
}
