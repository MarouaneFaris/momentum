import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useCreateInvitation } from '../queries'

const schema = z.object({
    email: z.string().email('Enter a valid email'),
    role: z.enum(['member', 'guest']),
})

type FormValues = z.infer<typeof schema>

type Props = {
    workspaceId: string
}

export function InviteForm({ workspaceId }: Props) {
    const { mutate, isPending } = useCreateInvitation(workspaceId)
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: { role: 'member' },
    })

    const onSubmit = (values: FormValues) => {
        mutate(
            { email: values.email, role: values.role },
            {
                onSuccess: () => {
                    toast.success('Invitation sent')
                    reset()
                },
                onError: (error) => {
                    if (error instanceof ApiError) {
                        toast.error(error.message)
                    }
                },
            },
        )
    }

    return (
        <form onSubmit={(e) => void handleSubmit(onSubmit)(e)} className="flex flex-col gap-4">
            <div className="grid gap-2">
                <Label htmlFor="invite-email">Email</Label>
                <Input
                    id="invite-email"
                    type="email"
                    placeholder="member@example.com"
                    {...register('email')}
                />
                {errors.email && <p className="text-sm text-destructive">{errors.email.message}</p>}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="invite-role">Role</Label>
                <select
                    id="invite-role"
                    className="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm"
                    {...register('role')}
                >
                    <option value="member">Member</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            <Button type="submit" disabled={isPending} className="w-fit">
                Send invitation
            </Button>
        </form>
    )
}
