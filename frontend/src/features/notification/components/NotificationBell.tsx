import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger } from '@/components/ui/popover'
import { Bell } from 'lucide-react'
import { useState } from 'react'
import { useNotificationActions } from '../hooks/useNotificationActions'
import { useNotifications } from '../hooks/useNotifications'
import { NotificationPanel } from './NotificationPanel'

export function NotificationBell() {
    const [open, setOpen] = useState(false)
    const { data } = useNotifications()
    const notifications = data ?? []
    const { handleMarkAllRead, handleMarkRead, handleDelete } = useNotificationActions()

    const hasUnread = notifications.some((n) => n.readAt === null)

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Notifications"
                    className="relative cursor-pointer"
                >
                    <Bell className="h-4 w-4" />
                    {hasUnread && (
                        <span className="border-sidebar bg-primary absolute top-[5px] right-[5px] h-[7px] w-[7px] rounded-full border-[1.5px]" />
                    )}
                </Button>
            </PopoverTrigger>
            <NotificationPanel
                notifications={notifications}
                onMarkAllRead={handleMarkAllRead}
                onMarkRead={handleMarkRead}
                onDelete={handleDelete}
            />
        </Popover>
    )
}
