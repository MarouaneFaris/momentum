import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateInvitation } from '../queries'

const schema = z.object({
    email: z.string().email('Enter a valid email'),
    role: z.enum(['member', 'guest']),
})

export type InviteFormValues = z.infer<typeof schema>

export const useInviteForm = (workspaceId: string) => {
    const { mutate, isPending } = useCreateInvitation(workspaceId)
    const queryClient = useQueryClient()

    const form = useForm<InviteFormValues>({
        resolver: zodResolver(schema),
        defaultValues: { role: 'member' },
    })

    const onSubmit = (values: InviteFormValues) => {
        mutate(
            { email: values.email, role: values.role },
            {
                onSuccess: () => {
                    void queryClient.invalidateQueries({
                        queryKey: ['workspaces', workspaceId, 'invitations'],
                    })
                    toast.success('Invitation sent')
                    form.reset()
                },
                onError: (error) => {
                    if (error instanceof ApiError) {
                        toast.error(error.message)
                    }
                },
            },
        )
    }

    return { form, isPending, onSubmit }
}
