import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Controller } from 'react-hook-form'
import { useInviteForm } from '../hooks/useInviteForm'

type Props = {
    workspaceId: string
}

export function InviteForm({ workspaceId }: Props) {
    const { form, isPending, onSubmit } = useInviteForm(workspaceId)
    const {
        register,
        control,
        handleSubmit,
        formState: { errors },
    } = form

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
                <Controller
                    control={control}
                    name="role"
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange}>
                            <SelectTrigger id="invite-role">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="member">Member</SelectItem>
                                <SelectItem value="guest">Guest</SelectItem>
                            </SelectContent>
                        </Select>
                    )}
                />
            </div>
            <Button type="submit" disabled={isPending} className="w-fit">
                Send invitation
            </Button>
        </form>
    )
}
