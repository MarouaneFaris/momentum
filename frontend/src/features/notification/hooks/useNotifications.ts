import { AuthContext } from '@/contexts/auth/AuthContext'
import api from '@/lib/api'
import { subscribe } from '@/lib/mercure'
import type { QueryClient } from '@tanstack/react-query'
import { useQueryClient } from '@tanstack/react-query'
import { useContext, useEffect } from 'react'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'
import type { Notification, NotificationEnvelope } from '../types'

function applyEnvelope(envelope: NotificationEnvelope, queryClient: QueryClient): void {
    if (envelope.op === 'created') {
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev ? [envelope.notification, ...prev] : [envelope.notification],
        )
    } else if (envelope.op === 'updated') {
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.map((n) => (n.id === envelope.notification.id ? envelope.notification : n)),
        )
    } else if (envelope.op === 'deleted') {
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.filter((n) => n.id !== envelope.id),
        )
    } else if (envelope.op === 'all-read') {
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.map((n) => (n.readAt === null ? { ...n, readAt: envelope.readAt } : n)),
        )
    } else {
        void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
    }
}

export const useNotifications = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()
    const query = useNotificationList()

    useEffect(() => {
        if (!isAuthenticated || !user?.id) return

        let cancelled = false
        let unsub: (() => void) | undefined
        let refreshTimer: ReturnType<typeof setTimeout> | undefined

        const connect = async () => {
            if (cancelled) return

            const result = await api.get<{ expiresIn: number }>('/notifications/mercure-token')
            if (cancelled || !result) return

            unsub?.()
            unsub = subscribe<NotificationEnvelope>(
                `/notifications/${user.id}`,
                (envelope) => applyEnvelope(envelope, queryClient),
                {
                    onError: () =>
                        void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY }),
                },
            )

            const refreshIn = (result.expiresIn - 30) * 1000
            if (refreshIn > 0) {
                refreshTimer = setTimeout(() => void connect(), refreshIn)
            }
        }

        void connect()

        return () => {
            cancelled = true
            clearTimeout(refreshTimer)
            unsub?.()
        }
    }, [isAuthenticated, user?.id, queryClient])

    return query
}
