import { AuthContext } from '@/contexts/auth/AuthContext'
import { subscribe } from '@/lib/mercure'
import { useQueryClient } from '@tanstack/react-query'
import { useContext, useEffect } from 'react'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'
import type { Notification, NotificationEnvelope } from '../types'

export const useNotifications = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()
    const query = useNotificationList()

    useEffect(() => {
        if (!isAuthenticated || !user?.id) return

        const unsub = subscribe<NotificationEnvelope>(
            `/notifications/${user.id}`,
            (envelope) => {
                if (envelope.op === 'created') {
                    queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                        prev ? [envelope.notification, ...prev] : [envelope.notification],
                    )
                } else if (envelope.op === 'updated') {
                    queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                        prev?.map((n) =>
                            n.id === envelope.notification.id ? envelope.notification : n,
                        ),
                    )
                } else if (envelope.op === 'deleted') {
                    queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                        prev?.filter((n) => n.id !== envelope.id),
                    )
                } else if (envelope.op === 'all-read') {
                    queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                        prev?.map((n) =>
                            n.readAt === null ? { ...n, readAt: envelope.readAt } : n,
                        ),
                    )
                } else {
                    void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
                }
            },
            {
                onError: () =>
                    void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY }),
            },
        )

        return unsub
    }, [isAuthenticated, user?.id, queryClient])

    return query
}
