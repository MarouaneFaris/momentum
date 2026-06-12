import api from '@/lib/api'
import { useFormMutation } from '@/hooks/useFormMutation'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import type { InvitationOwnerView, InvitationRole } from '../types'

const schema = z.object({
    email: z.string().email('Enter a valid email'),
    role: z.enum(['member', 'guest']),
})

export type InviteFormValues = z.infer<typeof schema>

export const useInviteForm = (workspaceId: string) => {
    const form = useForm<InviteFormValues>({
        resolver: zodResolver(schema),
        defaultValues: { role: 'member' as InvitationRole },
    })

    const { mutate, isPending } = useFormMutation({
        mutationFn: (data: InviteFormValues) =>
            api.post<InvitationOwnerView>(`/workspaces/${workspaceId}/invitations`, data),
        invalidateKey: ['workspaces', workspaceId, 'invitations'],
        onSuccess: () => {
            toast.success('Invitation sent')
            form.reset()
        },
    })

    const onSubmit = (values: InviteFormValues) => {
        mutate(values)
    }

    return { form, isPending, onSubmit }
}
