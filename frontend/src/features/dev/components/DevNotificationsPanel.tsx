import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useTriggerNotification } from '@/features/dev/queries'
import type { NotificationType } from '@/features/notification/types'
import { Bell } from 'lucide-react'

const NOTIFICATION_LABELS: Record<NotificationType, string> = {
    task_assigned_to_you: 'Task assigned to you',
    task_assigned_member: 'Task assigned to member',
    task_status_changed_yours: 'Your task status changed',
    task_status_changed_member: "Member's task status changed",
    invitation_received: 'Invitation received',
    invitation_accepted: 'Invitation accepted',
    invitation_declined: 'Invitation declined',
    invitation_cancelled: 'Invitation cancelled',
}

interface Props {
    onOpenChange: (open: boolean) => void
}

export default function DevNotificationsPanel({ onOpenChange }: Props) {
    const { mutate: trigger } = useTriggerNotification()

    return (
        <DropdownMenu onOpenChange={onOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="dark:bg-background dark:hover:bg-muted flex items-center gap-1.5 text-xs shadow-md"
                >
                    <Bell className="size-3" />
                    Notify
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-auto">
                {(Object.entries(NOTIFICATION_LABELS) as [NotificationType, string][]).map(
                    ([type, label]) => (
                        <DropdownMenuItem key={type} onClick={() => trigger(type)}>
                            {label}
                        </DropdownMenuItem>
                    ),
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
