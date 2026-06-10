import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger } from '@/components/ui/popover'
import { cn } from '@/lib/utils'
import { Bell } from 'lucide-react'
import { useState } from 'react'
import { useMarkAllNotificationsRead } from '../queries'
import { useDeleteNotification, useMarkNotificationRead } from '../hooks/useNotificationActions'
import { useNotifications } from '../hooks/useNotifications'
import { NotificationPanel } from './NotificationPanel'

export function NotificationBell() {
    const [open, setOpen] = useState(false)
    const { data } = useNotifications()
    const notifications = data ?? []
    const { mutate: markAllRead } = useMarkAllNotificationsRead()
    const { mutate: markRead } = useMarkNotificationRead()
    const { mutate: deleteNotification } = useDeleteNotification()

    const hasUnread = notifications.some((n) => n.readAt === null)

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Notifications"
                    className={cn('relative', open && 'bg-primary/10 text-primary')}
                >
                    <Bell className="h-4 w-4" />
                    {hasUnread && (
                        <span className="border-sidebar bg-primary absolute top-[5px] right-[5px] h-[7px] w-[7px] rounded-full border-[1.5px]" />
                    )}
                </Button>
            </PopoverTrigger>
            <NotificationPanel
                notifications={notifications}
                onMarkAllRead={() => markAllRead()}
                onMarkRead={(id) => markRead(id)}
                onDelete={(id) => deleteNotification(id)}
            />
        </Popover>
    )
}
