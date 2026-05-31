import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateWorkspace } from '../queries'
import { workspaceStorage } from '../workspaceStorage'

const schema = z.object({
    name: z.string().min(1, 'Name is required').max(64, 'Name must be 64 characters or fewer'),
})

type FormValues = z.infer<typeof schema>

export const useCreateWorkspaceForm = (onSuccess: () => void) => {
    const { mutate, isPending } = useCreateWorkspace()
    const navigate = useNavigate()

    const form = useForm<FormValues>({ resolver: zodResolver(schema) })

    const onSubmit = (values: FormValues) => {
        mutate(values, {
            onSuccess: (workspace) => {
                if (workspace) {
                    workspaceStorage.write(workspace.id)
                    onSuccess()
                    form.reset()
                    void navigate(`/workspaces/${workspace.id}/dashboard`)
                }
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
