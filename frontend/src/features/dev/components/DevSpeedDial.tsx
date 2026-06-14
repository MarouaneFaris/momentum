import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useDevLoginAction } from '@/features/dev/hooks/useDevLoginAction'
import { useDevUsers, useTriggerNotification } from '@/features/dev/queries'
import type { NotificationType } from '@/features/notification/types'
import { Bug, LogIn, Bell } from 'lucide-react'
import { useContext, useState, useEffect, useRef } from 'react'

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

export default function DevSpeedDial() {
    const [open, setOpen] = useState(false)
    const [loginDropOpen, setLoginDropOpen] = useState(false)
    const [notifyDropOpen, setNotifyDropOpen] = useState(false)
    const ref = useRef<HTMLDivElement>(null)
    const { isAuthenticated } = useContext(AuthContext)
    const { data } = useDevUsers()
    const users = data ?? []
    const { handleLoginAs } = useDevLoginAction()
    const { mutate: trigger } = useTriggerNotification()

    const anySubDropOpen = loginDropOpen || notifyDropOpen

    useEffect(() => {
        if (!open || anySubDropOpen) return
        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false)
            }
        }
        document.addEventListener('mousedown', handler)
        return () => document.removeEventListener('mousedown', handler)
    }, [open, anySubDropOpen])

    return (
        <div ref={ref} className="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-2">
            {open && (
                <>
                    {isAuthenticated && (
                        <DropdownMenu open={notifyDropOpen} onOpenChange={setNotifyDropOpen}>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex items-center gap-1.5 text-xs shadow-md"
                                >
                                    <Bell className="size-3" />
                                    Notify
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-auto">
                                {(
                                    Object.entries(NOTIFICATION_LABELS) as [
                                        NotificationType,
                                        string,
                                    ][]
                                ).map(([type, label]) => (
                                    <DropdownMenuItem key={type} onClick={() => trigger(type)}>
                                        {label}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                    <DropdownMenu open={loginDropOpen} onOpenChange={setLoginDropOpen}>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="outline"
                                size="sm"
                                className="flex items-center gap-1.5 text-xs shadow-md"
                            >
                                <LogIn className="size-3" />
                                Login
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-auto">
                            {users.map((user) => (
                                <DropdownMenuItem
                                    key={user.id}
                                    onClick={() => {
                                        setOpen(false)
                                        handleLoginAs(user.email)
                                    }}
                                >
                                    {user.email}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </>
            )}
            <Button
                variant="outline"
                size="icon"
                className="size-10 rounded-full shadow-lg"
                onClick={() => setOpen((v) => !v)}
                aria-label="Dev tools"
            >
                <Bug className="size-4" />
            </Button>
        </div>
    )
}
