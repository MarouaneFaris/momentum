import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import type { NotificationType } from '@/features/notification/types'
import { useTriggerNotification } from '@/features/dev/queries'

const NOTIFICATION_LABELS: Record<NotificationType, string> = {
    task_assigned_to_you: 'Task assigned to you',
    task_assigned_member: 'Task assigned to member',
    task_status_changed_yours: 'Your task status changed',
    task_status_changed_member: "Member's task status changed",
    invitation_received: 'Invitation received',
    invitation_accepted: 'Invitation accepted',
    invitation_declined: 'Invitation declined',
}

export default function DevNotificationsPanel() {
    const { mutate: trigger } = useTriggerNotification()

    if (!import.meta.env.DEV) return null

    return (
        <div className="fixed right-4 bottom-16 z-50">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" className="text-xs">
                        Dev Notify
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
        </div>
    )
}
