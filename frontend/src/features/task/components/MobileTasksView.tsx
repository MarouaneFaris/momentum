import { BottomSheet } from '@/components/BottomSheet'
import { Fab } from '@/components/Fab'
import { FilterChips } from '@/components/FilterChips'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/features/auth/queries'
import { useWorkspaceMembers } from '@/features/membership/queries'
import { cn } from '@/lib/utils'
import { ArrowLeft, ClipboardList, Info, MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { UseFormReturn } from 'react-hook-form'
import { useCreateTaskModal } from '../hooks/useCreateTaskModal'
import { useDeleteTaskDialog } from '../hooks/useDeleteTaskDialog'
import { useEditTaskModal } from '../hooks/useEditTaskModal'
import { useTaskDetail } from '../hooks/useTaskDetail'
import type { TaskFormValues } from '../hooks/useTaskForm'
import { useUpdateTaskStatus } from '../hooks/useUpdateTaskStatus'
import type { Task, TaskDetail, TaskStatus } from '../types'
import { DeleteTaskDialog } from './DeleteTaskDialog'

type MobileFilter = 'all' | TaskStatus

const FILTER_OPTIONS = [
    { label: 'All', value: 'all' },
    { label: 'To do', value: 'todo' },
    { label: 'In progress', value: 'in-progress' },
    { label: 'Done', value: 'done' },
]

const STATUS_OPTIONS: { value: TaskStatus; label: string }[] = [
    { value: 'todo', label: 'To do' },
    { value: 'in-progress', label: 'In progress' },
    { value: 'done', label: 'Done' },
]

function StatusBadge({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'bg-muted text-muted-foreground border-border',
        'in-progress': 'bg-primary/10 text-primary border-primary/25',
        done: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800/30',
    }[status]

    const label = { todo: 'To do', 'in-progress': 'In progress', done: 'Done' }[status]

    return (
        <Badge variant="outline" className={cn('shrink-0 text-[10px]', className)}>
            {label}
        </Badge>
    )
}

function StatusCircle({ status }: { status: TaskStatus }) {
    const className = {
        todo: 'border-border bg-muted',
        'in-progress': 'border-primary/40 bg-primary/20',
        done: 'border-green-400 bg-green-300 dark:border-green-600 dark:bg-green-700',
    }[status]

    return <div className={cn('h-4 w-4 shrink-0 rounded-full border', className)} />
}

function MiniAvatar({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase()

    return (
        <div className="bg-primary/15 border-primary/30 text-primary flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[8px] font-semibold">
            {initials}
        </div>
    )
}

function TaskRow({ task, onClick }: { task: Task; onClick: () => void }) {
    return (
        <button
            onClick={onClick}
            className="border-border bg-card active:bg-muted flex w-full items-center gap-2.5 rounded-[var(--radius)] border px-3 py-2.5 text-left transition-colors"
        >
            <StatusCircle status={task.status} />
            <span className="text-foreground flex-1 truncate text-sm leading-snug font-medium">
                {task.title}
            </span>
            <StatusBadge status={task.status} />
            {task.assignee && <MiniAvatar name={task.assignee.name} />}
        </button>
    )
}

export function MobileTaskList({
    tasks,
    isEmpty,
    isGuest,
    filter,
    onFilterChange,
    onTaskTap,
    onNewTask,
}: {
    tasks: Task[]
    isEmpty: boolean
    isGuest: boolean
    filter: MobileFilter
    onFilterChange: (f: MobileFilter) => void
    onTaskTap: (taskId: string) => void
    onNewTask: () => void
}) {
    const filtered = filter === 'all' ? tasks : tasks.filter((t) => t.status === filter)

    return (
        <div className="flex flex-col gap-3 p-4">
            <h1 className="text-base font-semibold tracking-tight">Tasks</h1>

            {isGuest && (
                <div className="bg-muted border-border text-muted-foreground flex items-center gap-2 rounded-[var(--radius)] border px-3 py-2 text-xs">
                    <Info className="size-3.5 shrink-0" />
                    You&apos;re viewing as a Guest — read-only access.
                </div>
            )}

            <FilterChips
                options={FILTER_OPTIONS}
                value={filter}
                onChange={(v) => onFilterChange(v as MobileFilter)}
            />

            {isEmpty ? (
                <div className="flex flex-col items-center gap-3 px-6 py-16 text-center">
                    <ClipboardList className="text-border size-10" />
                    <div className="text-foreground text-sm font-medium">No tasks yet</div>
                    <div className="text-muted-foreground text-[13px]">
                        Create a task to start tracking work for this project.
                    </div>
                    {!isGuest && (
                        <Button size="lg" className="mt-1" onClick={onNewTask}>
                            <Plus />
                            New task
                        </Button>
                    )}
                </div>
            ) : filtered.length === 0 ? (
                <p className="text-muted-foreground py-8 text-center text-sm">
                    No tasks match this filter.
                </p>
            ) : (
                <div className="flex flex-col gap-2">
                    {filtered.map((task) => (
                        <TaskRow key={task.id} task={task} onClick={() => onTaskTap(task.id)} />
                    ))}
                </div>
            )}

            <Fab icon={Plus} onClick={onNewTask} hidden={isGuest} />
        </div>
    )
}

function DetailRow({ label, children }: { label: string; children: React.ReactNode }) {
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
                                <MiniAvatar name={task.assignee.name} />
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
                            <MiniAvatar name={task.creator.name} />
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

function AssigneeChips({
    workspaceId,
    value,
    onChange,
}: {
    workspaceId: string
    value: string
    onChange: (id: string) => void
}) {
    const { data: members } = useWorkspaceMembers(workspaceId)
    const assignable = members?.filter((m) => m.role !== 'guest') ?? []

    if (assignable.length === 0) return null

    return (
        <div className="flex flex-col gap-1.5">
            <Label>Assignee</Label>
            <div className="flex [scrollbar-width:none] gap-2 overflow-x-auto [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    onClick={() => onChange('')}
                    className={cn(
                        'rounded-full border px-3 py-1 text-xs font-medium whitespace-nowrap transition-colors',
                        value === ''
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-muted text-muted-foreground',
                    )}
                >
                    Unassigned
                </button>
                {assignable.map((m) => (
                    <button
                        key={m.id}
                        type="button"
                        onClick={() => onChange(m.id)}
                        className={cn(
                            'rounded-full border px-3 py-1 text-xs font-medium whitespace-nowrap transition-colors',
                            value === m.id
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-muted text-muted-foreground',
                        )}
                    >
                        {m.name}
                    </button>
                ))}
            </div>
        </div>
    )
}

function TaskFormBottomSheet({
    open,
    onOpenChange,
    workspaceId,
    form,
    isPending,
    onSubmit,
    mode = 'create',
}: {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    form: UseFormReturn<TaskFormValues>
    isPending: boolean
    onSubmit: (values: TaskFormValues) => void
    mode?: 'create' | 'edit'
}) {
    const isEdit = mode === 'edit'
    const {
        register,
        handleSubmit,
        reset,
        setValue,
        watch,
        formState: { errors },
    } = form

    const assigneeId = watch('assigneeId') ?? ''
    const currentStatus = watch('status')

    return (
        <BottomSheet
            open={open}
            onOpenChange={(v) => {
                onOpenChange(v)
                if (!v) reset()
            }}
            title={isEdit ? 'Edit task' : 'Create task'}
        >
            <form
                id="mobile-task-form"
                onSubmit={(e) => void handleSubmit(onSubmit)(e)}
                className="flex flex-col gap-4 px-4 pt-4 pb-6"
            >
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="m-task-title">Title</Label>
                    <Input
                        id="m-task-title"
                        placeholder="Task title"
                        autoFocus
                        {...register('title')}
                    />
                    {errors.title && (
                        <p className="text-destructive text-sm">{errors.title.message}</p>
                    )}
                </div>

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="m-task-desc">Description</Label>
                    <Textarea
                        id="m-task-desc"
                        placeholder="Optional description"
                        rows={3}
                        {...register('description')}
                    />
                </div>

                {isEdit && (
                    <div className="flex flex-col gap-1.5">
                        <Label>Status</Label>
                        <div className="flex flex-wrap gap-2">
                            {STATUS_OPTIONS.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setValue('status', opt.value)}
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                        currentStatus === opt.value
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border bg-muted text-muted-foreground',
                                    )}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <AssigneeChips
                    workspaceId={workspaceId}
                    value={assigneeId}
                    onChange={(id) => setValue('assigneeId', id)}
                />

                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            onOpenChange(false)
                            reset()
                        }}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" form="mobile-task-form" disabled={isPending}>
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </div>
            </form>
        </BottomSheet>
    )
}

type Props = {
    workspaceId: string
    projectId: string
    tasks: Task[]
    isEmpty: boolean
    isGuest: boolean
    isOwner: boolean
    projectName: string | null
}

export function MobileTasksView({
    workspaceId,
    projectId,
    tasks,
    isEmpty,
    isGuest,
    isOwner,
    projectName,
}: Props) {
    const { data: authUser } = useAuth()
    const [filter, setFilter] = useState<MobileFilter>('all')

    const detail = useTaskDetail(workspaceId, projectId)
    const modal = useCreateTaskModal(workspaceId, projectId)
    const editModal = useEditTaskModal(workspaceId, projectId)
    const deleteDialog = useDeleteTaskDialog(workspaceId, projectId, detail.close)

    const canEdit = (t: TaskDetail) => !isGuest && (isOwner || t.creator.id === authUser?.id)

    if (detail.selectedTaskId !== null) {
        return (
            <>
                {detail.task ? (
                    <MobileTaskDetail
                        task={detail.task}
                        projectName={projectName}
                        workspaceId={workspaceId}
                        projectId={projectId}
                        isGuest={isGuest}
                        canEdit={canEdit(detail.task)}
                        onBack={detail.close}
                        onEdit={() => editModal.openFromDetail(detail.task!)}
                        onDelete={() => {
                            const t = detail.task!
                            deleteDialog.openDialog({
                                id: t.id,
                                title: t.title,
                                status: t.status,
                                assignee: t.assignee,
                                createdAt: t.createdAt,
                                creatorId: t.creator.id,
                            })
                        }}
                    />
                ) : (
                    <div className="flex flex-col p-4">
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={detail.close}
                            className="-ml-1 h-8 w-8"
                        >
                            <ArrowLeft size={18} />
                        </Button>
                    </div>
                )}
                <TaskFormBottomSheet
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

    return (
        <>
            <MobileTaskList
                tasks={tasks}
                isEmpty={isEmpty}
                isGuest={isGuest}
                filter={filter}
                onFilterChange={setFilter}
                onTaskTap={detail.open}
                onNewTask={() => modal.setOpen(true)}
            />
            <TaskFormBottomSheet
                open={modal.open}
                onOpenChange={modal.setOpen}
                workspaceId={workspaceId}
                form={modal.form}
                isPending={modal.isPending}
                onSubmit={modal.onSubmit}
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
