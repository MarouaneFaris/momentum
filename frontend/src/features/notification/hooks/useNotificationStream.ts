import { AuthContext } from '@/contexts/auth/AuthContext'
import { useMercureChannel } from '@/lib/useMercureChannel'
import { useQueryClient } from '@tanstack/react-query'
import { useCallback, useContext } from 'react'
import { applyEnvelope } from '../applyEnvelope'
import { useNotificationList } from '../queries'
import type { NotificationEnvelope } from '../types'

export const useNotificationStream = () => {
    const { user, isAuthenticated } = useContext(AuthContext)
    const queryClient = useQueryClient()

    const onMessage = useCallback(
        (envelope: NotificationEnvelope) => applyEnvelope(envelope, queryClient),
        [queryClient],
    )

    useMercureChannel<NotificationEnvelope>({
        topic: `/notifications/${user?.id ?? ''}`,
        onMessage,
        enabled: isAuthenticated && !!user?.id,
    })

    return useNotificationList()
}
