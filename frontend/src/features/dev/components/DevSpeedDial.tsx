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
import { Bell, Bug, LogIn } from 'lucide-react'
import { useContext, useEffect, useRef, useState } from 'react'

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

const STORAGE_KEY = 'dev-fab-pos'
const FAB_SIZE = 40
const DRAG_THRESHOLD = 5

interface FabPos {
    right: number
    bottom: number
}

function loadPos(): FabPos {
    try {
        const raw = localStorage.getItem(STORAGE_KEY)
        return raw ? (JSON.parse(raw) as FabPos) : { right: 16, bottom: 64 }
    } catch {
        return { right: 16, bottom: 64 }
    }
}

export default function DevSpeedDial() {
    const [open, setOpen] = useState(false)
    const [loginDropOpen, setLoginDropOpen] = useState(false)
    const [notifyDropOpen, setNotifyDropOpen] = useState(false)
    const [pos, setPos] = useState<FabPos>(loadPos)
    const ref = useRef<HTMLDivElement>(null)
    const drag = useRef<{
        startX: number
        startY: number
        startRight: number
        startBottom: number
        moved: boolean
    } | null>(null)

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

    function onPointerDown(e: React.PointerEvent<HTMLButtonElement>) {
        e.currentTarget.setPointerCapture(e.pointerId)
        drag.current = {
            startX: e.clientX,
            startY: e.clientY,
            startRight: pos.right,
            startBottom: pos.bottom,
            moved: false,
        }
    }

    function onPointerMove(e: React.PointerEvent<HTMLButtonElement>) {
        if (!drag.current) return
        const dx = e.clientX - drag.current.startX
        const dy = e.clientY - drag.current.startY
        if (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD) {
            drag.current.moved = true
        }
        if (!drag.current.moved) return
        const newRight = Math.max(
            0,
            Math.min(window.innerWidth - FAB_SIZE, drag.current.startRight - dx),
        )
        const newBottom = Math.max(
            0,
            Math.min(window.innerHeight - FAB_SIZE, drag.current.startBottom - dy),
        )
        setPos({ right: newRight, bottom: newBottom })
    }

    function onPointerUp() {
        if (!drag.current) return
        const { moved } = drag.current
        drag.current = null
        if (!moved) {
            setOpen((v) => !v)
        } else {
            setPos((p) => {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(p))
                return p
            })
        }
    }

    return (
        <div
            ref={ref}
            className="fixed z-50 flex flex-col items-end gap-2"
            style={{ right: pos.right, bottom: pos.bottom }}
        >
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
                className="size-10 cursor-grab rounded-full border-amber-500 bg-amber-500 text-white shadow-lg hover:bg-amber-600 active:cursor-grabbing dark:border-amber-500 dark:bg-amber-500 dark:text-white dark:hover:bg-amber-600"
                onPointerDown={onPointerDown}
                onPointerMove={onPointerMove}
                onPointerUp={onPointerUp}
                aria-label="Dev tools"
            >
                <Bug className="size-4" />
            </Button>
        </div>
    )
}
