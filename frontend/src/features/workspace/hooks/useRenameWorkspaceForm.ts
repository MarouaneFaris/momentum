import ApiError from '@/lib/ApiError'
import { useQueryClient } from '@tanstack/react-query'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useRenameWorkspace } from '../queries'
import type { Workspace } from '../types'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

export const useRenameWorkspaceForm = (workspace: Workspace) => {
    const { mutate, isPending } = useRenameWorkspace(workspace.id)
    const queryClient = useQueryClient()

    const form = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { name: workspace.name },
    })

    const onSubmit = (values: FormValues) => {
        mutate(values, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['workspaces'] })
                toast.success('Workspace renamed')
            },
            onError: (error) => {
                if (error instanceof ApiError) {
                    toast.error(error.message)
                }
            },
        })
    }

    return { form, isPending, onSubmit }
}
