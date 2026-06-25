import { useAuth } from '@/features/auth/queries'
import { useCreateTaskModal } from '@/features/task/hooks/useCreateTaskModal'
import { useDeleteTaskDialog } from '@/features/task/hooks/useDeleteTaskDialog'
import { useEditTaskModal } from '@/features/task/hooks/useEditTaskModal'
import { useTaskDetail } from '@/features/task/hooks/useTaskDetail'
import { useWorkspaceProjectTasksPage } from '@/features/task/hooks/useWorkspaceProjectTasksPage'
import { DeleteTaskDialog } from '@/features/task/components/DeleteTaskDialog'
import { MobileTasksView } from '@/features/task/components/MobileTasksView'
import { TaskBoard } from '@/features/task/components/TaskBoard'
import TaskDetailPanel from '@/features/task/components/TaskDetailPanel'
import { TaskFormModal } from '@/features/task/components/TaskFormModal'
import { useIsMobile } from '@/hooks/useIsMobile'
import { projectColorValue } from '../projectColor'
import { useProjectDetail } from '../queries'

function StatPill({ label, count, color }: { label: string; count: number; color: string }) {
    return (
        <span className="bg-muted/60 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium">
            <span className="size-2 shrink-0 rounded-full" style={{ backgroundColor: color }} />
            <span className="text-foreground tabular-nums">{count}</span>
            <span className="text-muted-foreground">{label}</span>
        </span>
    )
}

export default function ProjectDetailPage() {
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

    const { data: project } = useProjectDetail(workspaceId, projectId)
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
    const colorValue = project ? projectColorValue(project.color) : undefined

    return (
        <>
            <div className="flex h-full overflow-hidden">
                <div
                    className={[
                        'flex min-w-0 flex-1 flex-col overflow-y-auto transition-[border-color] duration-200',
                        panelOpen ? 'border-border border-r' : 'border-r border-transparent',
                    ].join(' ')}
                >
                    {project && (
                        <div className="border-border flex shrink-0 flex-wrap items-center gap-3 border-b px-6 py-3">
                            <div className="flex items-center gap-2">
                                <span
                                    className="size-3 shrink-0 rounded-full"
                                    style={{ backgroundColor: colorValue }}
                                />
                                <span className="text-foreground text-sm font-semibold">
                                    {project.name}
                                </span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <StatPill
                                    label="todo"
                                    count={project.taskStats.todo}
                                    color="hsl(var(--muted-foreground))"
                                />
                                <StatPill
                                    label="in progress"
                                    count={project.taskStats.inProgress}
                                    color="oklch(0.6 0.15 200)"
                                />
                                <StatPill
                                    label="done"
                                    count={project.taskStats.done}
                                    color="oklch(0.55 0.15 145)"
                                />
                                <span className="text-muted-foreground px-1 text-xs">·</span>
                                <span className="text-muted-foreground text-xs">
                                    {project.memberCount} member
                                    {project.memberCount !== 1 ? 's' : ''}
                                </span>
                            </div>
                        </div>
                    )}
                    <div className="flex flex-1 flex-col gap-5 p-6">
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
                    </div>
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
                                    dueDate: detail.task.dueDate,
                                    isOverdue: detail.task.isOverdue,
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
