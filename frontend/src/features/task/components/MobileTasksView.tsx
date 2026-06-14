import { Button } from '@/components/ui/button'
import { useAuth } from '@/features/auth/queries'
import { ArrowLeft } from 'lucide-react'
import { useState } from 'react'
import { useCreateTaskModal } from '../hooks/useCreateTaskModal'
import { useDeleteTaskDialog } from '../hooks/useDeleteTaskDialog'
import { useEditTaskModal } from '../hooks/useEditTaskModal'
import { useTaskDetail } from '../hooks/useTaskDetail'
import type { Task, TaskDetail } from '../types'
import { DeleteTaskDialog } from './DeleteTaskDialog'
import { MobileTaskDetail } from './MobileTaskDetail'
import type { MobileFilter } from './MobileTaskList'
import { MobileTaskList } from './MobileTaskList'
import { TaskFormBottomSheet } from './TaskFormBottomSheet'

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
                workspaceId={workspaceId}
                projectName={projectName}
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
