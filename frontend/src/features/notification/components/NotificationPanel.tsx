import { Button } from '@/components/ui/button'
import { PopoverContent } from '@/components/ui/popover'
import type { Notification } from '../types'
import { NotificationItem } from './NotificationItem'

type Props = {
    notifications: Notification[]
    onMarkAllRead: () => void
}

export function NotificationPanel({ notifications, onMarkAllRead }: Props) {
    return (
        <PopoverContent align="end" className="w-80 p-0">
            <div className="flex items-center justify-between border-b px-3 py-2">
                <span className="text-sm font-semibold">Notifications</span>
                {notifications.length > 0 && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-muted-foreground h-auto px-1 py-0.5 text-xs"
                        onClick={onMarkAllRead}
                    >
                        Mark all read
                    </Button>
                )}
            </div>
            {notifications.length === 0 ? (
                <p className="text-muted-foreground px-3 py-6 text-center text-sm">
                    No notifications
                </p>
            ) : (
                <div className="max-h-96 divide-y overflow-y-auto">
                    {notifications.map((n) => (
                        <NotificationItem key={n.id} notification={n} />
                    ))}
                </div>
            )}
        </PopoverContent>
    )
}
