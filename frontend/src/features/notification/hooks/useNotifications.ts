import { AuthContext } from '@/contexts/auth/AuthContext'
import { useQueryClient } from '@tanstack/react-query'
import { useContext, useEffect } from 'react'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'
import type { Notification, NotificationEnvelope } from '../types'

const getMercureUrl = () => {
    const apiUrl = import.meta.env.VITE_API_URL as string
    return apiUrl.replace(/\/api$/, '/.well-known/mercure')
}

export const useNotifications = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()
    const query = useNotificationList()

    useEffect(() => {
        if (!isAuthenticated || !user?.id) return

        const url = new URL(getMercureUrl())
        url.searchParams.append('topic', `/notifications/${user.id}`)

        const es = new EventSource(url.toString())

        es.onmessage = (event: MessageEvent) => {
            try {
                const envelope = JSON.parse(event.data as string) as NotificationEnvelope

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
            } catch {
                void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
            }
        }

        return () => {
            es.close()
        }
    }, [isAuthenticated, user?.id, queryClient])

    return query
}
