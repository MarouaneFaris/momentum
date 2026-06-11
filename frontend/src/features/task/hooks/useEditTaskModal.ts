import { useEffect, useMemo, useState } from 'react'
import { useTask } from '../queries'
import type { Task, TaskDetail, TaskStatus } from '../types'
import { useTaskForm } from './useTaskForm'

type EditState = {
    id: string
    title: string
    description: string
    assigneeId: string
    status: TaskStatus
}

function fromDetail(t: TaskDetail): EditState {
    return {
        id: t.id,
        title: t.title,
        description: t.description ?? '',
        assigneeId: t.assignee?.id ?? '',
        status: t.status,
    }
}

export function useEditTaskModal(workspaceId: string, projectId: string) {
    const [rawState, setRawState] = useState<EditState | null>(null)
    const [fetchId, setFetchId] = useState<string | null>(null)

    const { data: fetchedDetail } = useTask(workspaceId, projectId, fetchId)

    const state = useMemo(() => {
        if (fetchedDetail && rawState?.id === fetchedDetail.id) {
            return fromDetail(fetchedDetail)
        }
        return rawState
    }, [fetchedDetail, rawState])

    const open = (t: Task) => {
        setRawState({
            id: t.id,
            title: t.title,
            description: '',
            assigneeId: t.assignee?.id ?? '',
            status: t.status,
        })
        setFetchId(t.id)
    }
    const openFromDetail = (t: TaskDetail) => setRawState(fromDetail(t))
    const close = () => {
        setRawState(null)
        setFetchId(null)
    }

    const { form, isPending, onSubmit } = useTaskForm({
        workspaceId,
        projectId,
        mode: 'edit',
        taskId: state?.id ?? '__placeholder__',
        initialValues: {
            title: state?.title ?? '',
            description: state?.description ?? '',
            assigneeId: state?.assigneeId ?? '',
            status: state?.status,
        },
        onSuccess: close,
    })

    useEffect(() => {
        if (state) {
            form.reset({
                title: state.title,
                description: state.description,
                assigneeId: state.assigneeId,
                status: state.status,
            })
        }
    }, [state, form])

    return {
        isOpen: state !== null,
        open,
        openFromDetail,
        close,
        form,
        isPending,
        onSubmit,
    }
}
