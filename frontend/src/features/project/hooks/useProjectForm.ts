import api from '@/lib/api'
import { useFormMutation } from '@/hooks/useFormMutation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import z from 'zod'
import type { Project } from '../types'
import type { UseProjectFormOptions } from '../types'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(255, 'Name must be 255 characters or fewer'),
    description: z.string().optional(),
    status: z.enum(['draft', 'active', 'archived']).optional(),
})

type FormValues = z.infer<typeof schema>
type ProjectPayload = { name: string; description?: string; status?: string }

export const useProjectForm = ({ workspaceId, project, onSuccess }: UseProjectFormOptions) => {
    const form = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            name: project?.name ?? '',
            description: project?.description ?? '',
            status: project?.status ?? 'active',
        },
    })

    const createMutation = useFormMutation({
        mutationFn: (data: ProjectPayload) =>
            api.post<Project>(`/workspaces/${workspaceId}/projects`, data),
        invalidateKey: ['workspaces', workspaceId, 'projects'],
        onSuccess: () => {
            onSuccess()
            form.reset()
        },
    })

    const updateMutation = useFormMutation({
        mutationFn: (data: ProjectPayload) =>
            api.patch<Project>(`/workspaces/${workspaceId}/projects/${project?.id ?? ''}`, data),
        invalidateKey: ['workspaces', workspaceId, 'projects'],
        onSuccess: () => {
            onSuccess()
            form.reset()
        },
    })

    const isPending = createMutation.isPending || updateMutation.isPending

    const onSubmit = (values: FormValues) => {
        const payload: ProjectPayload = {
            name: values.name,
            description: values.description,
            status: values.status,
        }
        if (project) {
            updateMutation.mutate(payload)
        } else {
            createMutation.mutate(payload)
        }
    }

    return { form, isPending, onSubmit }
}
