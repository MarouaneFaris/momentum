import api from '@/lib/api'
import { useFormMutation } from '@/hooks/useFormMutation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import type { Workspace } from '../types'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

export const useRenameWorkspaceForm = (workspace: Workspace) => {
    const { mutate, isPending } = useFormMutation({
        mutationFn: (data: FormValues) => api.patch<Workspace>(`/workspaces/${workspace.id}`, data),
        invalidateKey: ['workspaces'],
        onSuccess: () => {
            toast.success('Workspace renamed')
        },
    })

    const form = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { name: workspace.name },
    })

    const onSubmit = (values: FormValues) => {
        mutate(values)
    }

    return { form, isPending, onSubmit }
}
