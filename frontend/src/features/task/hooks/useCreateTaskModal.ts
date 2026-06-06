import { useState } from 'react'
import { useTaskForm } from './useTaskForm'

export function useCreateTaskModal(workspaceId: string, projectId: string) {
    const [open, setOpen] = useState(false)

    const { form, isPending, onSubmit } = useTaskForm({
        workspaceId,
        projectId,
        onSuccess: () => setOpen(false),
    })

    return {
        open,
        setOpen,
        form,
        isPending,
        onSubmit,
    }
}
