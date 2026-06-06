import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateTask, useUpdateTask } from '../queries'

const schema = z.object({
    title: z.string().min(1, 'Title is required').max(255, 'Title must be 255 characters or fewer'),
    description: z.string().optional(),
    assigneeId: z.string().optional(),
})

export type TaskFormValues = z.infer<typeof schema>

export type UseTaskFormOptions = {
    workspaceId: string
    projectId: string
    onSuccess: () => void
} & (
    | { mode?: 'create'; taskId?: undefined; initialValues?: undefined }
    | { mode: 'edit'; taskId: string; initialValues: TaskFormValues }
)

export function useTaskForm({ workspaceId, projectId, onSuccess, ...rest }: UseTaskFormOptions) {
    const queryClient = useQueryClient()
    const createMutation = useCreateTask(workspaceId, projectId)
    const updateMutation = useUpdateTask(
        workspaceId,
        projectId,
        rest.mode === 'edit' ? rest.taskId : '',
    )

    const isEdit = rest.mode === 'edit'

    const form = useForm<TaskFormValues>({
        resolver: zodResolver(schema),
        defaultValues: isEdit
            ? rest.initialValues
            : {
                  title: '',
                  description: '',
                  assigneeId: '',
              },
    })

    const isPending = isEdit ? updateMutation.isPending : createMutation.isPending

    const onSubmit = (values: TaskFormValues) => {
        const invalidateKey = ['workspaces', workspaceId, 'projects', projectId, 'tasks']

        const payload = {
            title: values.title,
            description: values.description || undefined,
            assigneeId: values.assigneeId || undefined,
        }
        const onError = (error: Error) => {
            if (error instanceof ApiError) toast.error(error.message)
        }

        if (isEdit) {
            updateMutation.mutate(payload, {
                onSuccess: () => {
                    void queryClient.invalidateQueries({ queryKey: invalidateKey })
                    onSuccess()
                },
                onError,
            })
            return
        }

        createMutation.mutate(payload, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: invalidateKey })
                onSuccess()
                form.reset()
            },
            onError,
        })
    }

    return { form, isPending, onSubmit }
}
