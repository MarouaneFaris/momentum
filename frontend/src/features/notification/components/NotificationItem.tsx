import { cn } from '@/lib/utils'
import type { Notification, NotificationType } from '../types'

const relativeTime = (isoDate: string): string => {
    const diff = Date.now() - new Date(isoDate).getTime()
    const minutes = Math.floor(diff / 60_000)
    if (minutes < 1) return 'just now'
    if (minutes < 60) return `${minutes}m ago`
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours}h ago`
    const days = Math.floor(hours / 24)
    if (days === 1) return 'Yesterday'
    return `${days}d ago`
}

const renderText = (type: NotificationType, payload: Notification['payload']): React.ReactNode => {
    switch (type) {
        case 'task_assigned_to_you': {
            const p = payload as { task_title: string }
            return (
                <>
                    You were assigned <strong>{p.task_title}</strong>
                </>
            )
        }
        case 'task_assigned_member': {
            const p = payload as { assignee_name: string; task_title: string }
            return (
                <>
                    <strong>{p.assignee_name}</strong> was assigned <strong>{p.task_title}</strong>
                </>
            )
        }
        case 'task_status_changed_yours': {
            const p = payload as { task_title: string; new_status: string }
            return (
                <>
                    <strong>{p.task_title}</strong> was marked <strong>{p.new_status}</strong>
                </>
            )
        }
        case 'task_status_changed_member': {
            const p = payload as { actor_name: string; task_title: string; new_status: string }
            return (
                <>
                    <strong>{p.actor_name}</strong> updated <strong>{p.task_title}</strong> to{' '}
                    <strong>{p.new_status}</strong>
                </>
            )
        }
        case 'invitation_received': {
            const p = payload as { workspace_name: string; role_name: string }
            return (
                <>
                    You were invited to <strong>{p.workspace_name}</strong> as{' '}
                    <strong>{p.role_name}</strong>
                </>
            )
        }
        case 'invitation_accepted': {
            const p = payload as { actor_name: string; workspace_name: string }
            return (
                <>
                    <strong>{p.actor_name}</strong> accepted your invitation to{' '}
                    <strong>{p.workspace_name}</strong>
                </>
            )
        }
        case 'invitation_declined': {
            const p = payload as { actor_name: string; workspace_name: string }
            return (
                <>
                    <strong>{p.actor_name}</strong> declined your invitation to{' '}
                    <strong>{p.workspace_name}</strong>
                </>
            )
        }
    }
}

type Props = {
    notification: Notification
}

export function NotificationItem({ notification }: Props) {
    const isRead = notification.read_at !== null

    return (
        <div className="flex items-start gap-3 px-3 py-2.5">
            <span
                className={cn(
                    'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                    isRead ? 'bg-muted-foreground/30' : 'bg-blue-500',
                )}
                aria-label={isRead ? 'read' : 'unread'}
            />
            <div className="min-w-0 flex-1">
                <p className="text-sm leading-snug">
                    {renderText(notification.type, notification.payload)}
                </p>
                <p className="text-muted-foreground mt-0.5 text-xs">
                    {relativeTime(notification.created_at)}
                </p>
            </div>
        </div>
    )
}
