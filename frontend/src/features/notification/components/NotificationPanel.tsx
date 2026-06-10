import { Button } from '@/components/ui/button'
import { PopoverContent } from '@/components/ui/popover'
import { Bell } from 'lucide-react'
import type { Notification } from '../types'
import { NotificationItem } from './NotificationItem'

type Props = {
    notifications: Notification[]
    onMarkAllRead: () => void
    onMarkRead: (id: string) => void
    onDelete: (id: string) => void
}

export function NotificationPanel({ notifications, onMarkAllRead, onMarkRead, onDelete }: Props) {
    const unreadCount = notifications.filter((n) => n.readAt === null).length

    return (
        <PopoverContent align="end" className="w-80 p-0">
            <div className="flex items-center gap-2 border-b px-3.5 py-3">
                <span className="flex-1 text-[13px] font-semibold">Notifications</span>
                {unreadCount > 0 && (
                    <span className="bg-primary text-primary-foreground rounded-full px-1.5 py-px text-[10px] leading-tight font-semibold">
                        {unreadCount} new
                    </span>
                )}
                {notifications.length > 0 && (
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-primary h-auto cursor-pointer px-1 py-0.5 text-[11px] font-medium"
                        onClick={onMarkAllRead}
                    >
                        Mark all read
                    </Button>
                )}
            </div>
            {notifications.length === 0 ? (
                <div className="flex flex-col items-center gap-2 px-5 py-10 text-center">
                    <div className="bg-muted text-muted-foreground flex h-9 w-9 items-center justify-center rounded-full">
                        <Bell className="h-[18px] w-[18px]" />
                    </div>
                    <p className="text-[13px] font-medium">No notifications</p>
                    <p className="text-muted-foreground text-xs leading-relaxed">
                        You&apos;re all caught up.
                        <br />
                        Activity will appear here.
                    </p>
                </div>
            ) : (
                <div className="max-h-96 divide-y overflow-y-auto">
                    {notifications.map((n) => (
                        <NotificationItem
                            key={n.id}
                            notification={n}
                            onMarkRead={onMarkRead}
                            onDelete={onDelete}
                        />
                    ))}
                </div>
            )}
        </PopoverContent>
    )
}
