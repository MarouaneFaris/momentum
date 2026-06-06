import { useEffect, useState } from 'react'
import type { Task } from '../types'
import { useTaskForm } from './useTaskForm'

export function useEditTaskModal(workspaceId: string, projectId: string) {
    const [task, setTask] = useState<Task | null>(null)

    const open = (t: Task) => setTask(t)
    const close = () => setTask(null)

    const { form, isPending, onSubmit } = useTaskForm({
        workspaceId,
        projectId,
        mode: 'edit',
        taskId: task?.id ?? '__placeholder__',
        initialValues: {
            title: task?.title ?? '',
            description: '',
            assigneeId: task?.assignee?.id ?? '',
        },
        onSuccess: close,
    })

    useEffect(() => {
        if (task) {
            form.reset({
                title: task.title,
                description: '',
                assigneeId: task.assignee?.id ?? '',
            })
        }
    }, [task, form])

    return {
        task,
        open,
        close,
        form,
        isPending,
        onSubmit,
    }
}
