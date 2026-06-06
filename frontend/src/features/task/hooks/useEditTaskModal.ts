import { useEffect, useState } from 'react'
import type { Task, TaskDetail } from '../types'
import { useTaskForm } from './useTaskForm'

type EditState = {
    id: string
    title: string
    description: string
    assigneeId: string
}

function fromTask(t: Task): EditState {
    return { id: t.id, title: t.title, description: '', assigneeId: t.assignee?.id ?? '' }
}

function fromDetail(t: TaskDetail): EditState {
    return {
        id: t.id,
        title: t.title,
        description: t.description ?? '',
        assigneeId: t.assignee?.id ?? '',
    }
}

export function useEditTaskModal(workspaceId: string, projectId: string) {
    const [state, setState] = useState<EditState | null>(null)

    const open = (t: Task) => setState(fromTask(t))
    const openFromDetail = (t: TaskDetail) => setState(fromDetail(t))
    const close = () => setState(null)

    const { form, isPending, onSubmit } = useTaskForm({
        workspaceId,
        projectId,
        mode: 'edit',
        taskId: state?.id ?? '__placeholder__',
        initialValues: {
            title: state?.title ?? '',
            description: state?.description ?? '',
            assigneeId: state?.assigneeId ?? '',
        },
        onSuccess: close,
    })

    useEffect(() => {
        if (state) {
            form.reset({
                title: state.title,
                description: state.description,
                assigneeId: state.assigneeId,
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
