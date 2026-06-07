import { Button } from '@/components/ui/button'
import { Popover, PopoverTrigger } from '@/components/ui/popover'
import { Bell } from 'lucide-react'
import { useState } from 'react'
import { useMarkAllNotificationsRead } from '../queries'
import { useNotifications } from '../hooks/useNotifications'
import { NotificationPanel } from './NotificationPanel'

export function NotificationBell() {
    const [open, setOpen] = useState(false)
    const { data } = useNotifications()
    const notifications = data ?? []
    const { mutate: markAllRead } = useMarkAllNotificationsRead()

    const hasUnread = notifications.some((n) => n.readAt === null)

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button variant="ghost" size="icon" aria-label="Notifications" className="relative">
                    <Bell className="h-4 w-4" />
                    {hasUnread && (
                        <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-blue-500" />
                    )}
                </Button>
            </PopoverTrigger>
            <NotificationPanel notifications={notifications} onMarkAllRead={() => markAllRead()} />
        </Popover>
    )
}
