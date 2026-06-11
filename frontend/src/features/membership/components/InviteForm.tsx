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
        <form
            onSubmit={(e) => void handleSubmit(onSubmit)(e)}
            className="bg-muted/50 flex flex-wrap items-end gap-3 rounded-md border p-4"
        >
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="invite-email" className="text-xs">
                    Email address
                </Label>
                <div className="flex flex-col gap-1">
                    <Input
                        id="invite-email"
                        type="email"
                        placeholder="colleague@company.com"
                        className="bg-background dark:bg-background h-8 w-72 text-sm"
                        {...register('email')}
                    />
                    {errors.email && (
                        <p className="text-destructive text-xs">{errors.email.message}</p>
                    )}
                </div>
            </div>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="invite-role" className="text-xs">
                    Role
                </Label>
                <Controller
                    control={control}
                    name="role"
                    render={({ field }) => (
                        <Select value={field.value} onValueChange={field.onChange}>
                            <SelectTrigger
                                id="invite-role"
                                size="sm"
                                className="bg-background dark:bg-background w-28"
                            >
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
            <Button type="submit" size="sm" disabled={isPending}>
                Send invitation
            </Button>
        </form>
    )
}
