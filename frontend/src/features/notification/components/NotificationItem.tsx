import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { Clock, FileText, X } from 'lucide-react'
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

const isTaskType = (type: NotificationType): boolean =>
    type === 'task_assigned_to_you' ||
    type === 'task_assigned_member' ||
    type === 'task_status_changed_yours' ||
    type === 'task_status_changed_member'

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
        case 'invitation_cancelled': {
            const p = payload as { workspace_name: string; role_name: string }
            return (
                <>
                    Your invitation to <strong>{p.workspace_name}</strong> as{' '}
                    <strong>{p.role_name}</strong> was cancelled
                </>
            )
        }
    }
}

type Props = {
    notification: Notification
    onMarkRead: (id: string) => void
    onDelete: (id: string) => void
}

export function NotificationItem({ notification, onMarkRead, onDelete }: Props) {
    const isRead = notification.readAt !== null
    const isDemo = notification.payload.demo === true
    const isTask = isTaskType(notification.type)

    const handleClick = () => {
        if (!isRead) onMarkRead(notification.id)
    }

    const handleDelete = (e: React.MouseEvent) => {
        e.stopPropagation()
        onDelete(notification.id)
    }

    return (
        <div
            className={cn(
                'group relative flex cursor-pointer items-start gap-2.5 px-3.5 py-2.5 transition-colors',
                isRead ? 'hover:bg-muted' : 'bg-primary/[0.03] hover:bg-primary/[0.06]',
            )}
            onClick={handleClick}
        >
            <div
                className={cn(
                    'mt-0.5 flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-md',
                    isTask
                        ? 'bg-primary/10 text-primary'
                        : 'bg-green-500/10 text-green-700 dark:text-green-400',
                )}
            >
                {isTask ? <FileText className="h-3 w-3" /> : <Clock className="h-3 w-3" />}
            </div>
            <span
                className={cn(
                    'mt-[5px] h-[7px] w-[7px] shrink-0 rounded-full',
                    isRead ? 'bg-border' : 'bg-primary',
                )}
                aria-label={isRead ? 'read' : 'unread'}
            />
            <div className="min-w-0 flex-1">
                <p
                    className={cn(
                        'text-xs leading-snug',
                        isRead ? 'text-muted-foreground' : 'text-foreground',
                        isDemo && 'opacity-60',
                    )}
                >
                    {renderText(notification.type, notification.payload)}
                </p>
                <p className="text-muted-foreground mt-0.5 text-[10px]">
                    {relativeTime(notification.createdAt)}
                    {isDemo && <span className="ml-1.5 italic">demo</span>}
                </p>
            </div>
            <Button
                variant="ghost"
                size="icon"
                className="absolute top-1.5 right-1.5 hidden h-6 w-6 cursor-pointer group-hover:flex"
                onClick={handleDelete}
                aria-label="Delete notification"
            >
                <X className="h-3 w-3" />
            </Button>
        </div>
    )
}
