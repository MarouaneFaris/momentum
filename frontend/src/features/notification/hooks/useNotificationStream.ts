import { AuthContext } from '@/contexts/auth/AuthContext'
import { useMercureChannel } from '@/lib/useMercureChannel'
import { useQueryClient } from '@tanstack/react-query'
import { useCallback, useContext } from 'react'
import { applyEnvelope } from '../applyEnvelope'
import { NOTIFICATIONS_QUERY_KEY, useNotificationList } from '../queries'
import type { NotificationEnvelope } from '../types'

export const useNotificationStream = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()

    const onMessage = useCallback(
        (envelope: NotificationEnvelope) => applyEnvelope(envelope, queryClient),
        [queryClient],
    )

    const onError = useCallback(
        () => void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY }),
        [queryClient],
    )

    useMercureChannel<NotificationEnvelope>({
        topic: `/notifications/${user?.id ?? ''}`,
        onMessage,
        onError,
        enabled: isAuthenticated && !!user?.id,
    })

    return useNotificationList()
}
