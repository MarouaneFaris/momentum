import api from '@/lib/api'
import { useFormMutation } from '@/hooks/useFormMutation'
import { ROUTES } from '@/lib/routes'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import z from 'zod'
import type { Workspace } from '../types'
import { workspaceStorage } from '../workspaceStorage'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

export const useCreateWorkspaceForm = (onSuccess: () => void) => {
    const navigate = useNavigate()

    const { mutate, isPending } = useFormMutation({
        mutationFn: (data: FormValues) => api.post<Workspace>(ROUTES.workspaces, data),
        invalidateKey: ['workspaces'],
        onSuccess: (workspace) => {
            if (workspace) {
                workspaceStorage.write(workspace.id)
                onSuccess()
                void navigate(`/workspaces/${workspace.id}/dashboard`)
            }
        },
    })

    const form = useForm<FormValues>({ resolver: zodResolver(schema) })

    const onSubmit = (values: FormValues) => {
        mutate(values)
    }

    return { form, isPending, onSubmit }
}
