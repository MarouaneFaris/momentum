import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateTask } from '../queries'

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
}

export function useTaskForm({ workspaceId, projectId, onSuccess }: UseTaskFormOptions) {
    const queryClient = useQueryClient()
    const createMutation = useCreateTask(workspaceId, projectId)

    const form = useForm<TaskFormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            title: '',
            description: '',
            assigneeId: '',
        },
    })

    const isPending = createMutation.isPending

    const onSubmit = (values: TaskFormValues) => {
        createMutation.mutate(
            {
                title: values.title,
                description: values.description || undefined,
                assigneeId: values.assigneeId || undefined,
            },
            {
                onSuccess: () => {
                    void queryClient.invalidateQueries({
                        queryKey: ['workspaces', workspaceId, 'projects', projectId, 'tasks'],
                    })
                    onSuccess()
                    form.reset()
                },
                onError: (error: Error) => {
                    if (error instanceof ApiError) {
                        toast.error(error.message)
                    }
                },
            },
        )
    }

    return { form, isPending, onSubmit }
}
