import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateProject, useUpdateProject } from '../queries'
import type { UseProjectFormOptions } from '../types'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(255, 'Name must be 255 characters or fewer'),
    description: z.string().optional(),
    status: z.enum(['draft', 'active', 'archived']).optional(),
})

type FormValues = z.infer<typeof schema>

export const useProjectForm = ({ workspaceId, project, onSuccess }: UseProjectFormOptions) => {
    const queryClient = useQueryClient()
    const createMutation = useCreateProject(workspaceId)
    const updateMutation = useUpdateProject(workspaceId, project?.id ?? '')

    const form = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            name: project?.name ?? '',
            description: project?.description ?? '',
            status: project?.status ?? 'active',
        },
    })

    const isPending = createMutation.isPending || updateMutation.isPending

    const onSubmit = (values: FormValues) => {
        const handleSuccess = () => {
            void queryClient.invalidateQueries({
                queryKey: ['workspaces', workspaceId, 'projects'],
            })
            onSuccess()
            form.reset()
        }

        const handleError = (error: Error) => {
            if (error instanceof ApiError) {
                toast.error(error.message)
            }
        }

        if (project) {
            updateMutation.mutate(
                { name: values.name, description: values.description, status: values.status },
                { onSuccess: handleSuccess, onError: handleError },
            )
        } else {
            createMutation.mutate(
                { name: values.name, description: values.description, status: values.status },
                { onSuccess: handleSuccess, onError: handleError },
            )
        }
    }

    return { form, isPending, onSubmit }
}
