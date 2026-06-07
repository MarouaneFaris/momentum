import { AuthContext } from '@/contexts/auth/AuthContext'
import api from '@/lib/api'
import { subscribe } from '@/lib/mercure'
import { useQueryClient } from '@tanstack/react-query'
import { useContext, useEffect } from 'react'
import { applyEnvelope } from '../applyEnvelope'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'
import type { NotificationEnvelope } from '../types'

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
