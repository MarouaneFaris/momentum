import { useAuth } from '@/features/auth/queries'
import { useIsMobile } from '@/hooks/useIsMobile'

import { useCreateTaskModal } from '../hooks/useCreateTaskModal'
import { useDeleteTaskDialog } from '../hooks/useDeleteTaskDialog'
import { useEditTaskModal } from '../hooks/useEditTaskModal'
import { useTaskDetail } from '../hooks/useTaskDetail'
import { useWorkspaceProjectTasksPage } from '../hooks/useWorkspaceProjectTasksPage'
import { DeleteTaskDialog } from './DeleteTaskDialog'
import { MobileTasksView } from './MobileTasksView'
import { TaskBoard } from './TaskBoard'
import TaskDetailPanel from './TaskDetailPanel'
import { TaskFormModal } from './TaskFormModal'

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

    return (
        <>
            <div className="flex h-full overflow-hidden">
                <div
                    className={[
                        'flex min-w-0 flex-1 flex-col gap-5 overflow-y-auto p-6 transition-[border-color] duration-200',
                        panelOpen ? 'border-border border-r' : 'border-r border-transparent',
                    ].join(' ')}
                >
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
