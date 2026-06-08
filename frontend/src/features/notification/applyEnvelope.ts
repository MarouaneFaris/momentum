import type { QueryClient } from '@tanstack/react-query'
import { NOTIFICATIONS_QUERY_KEY } from './queries'
import type { Notification, NotificationEnvelope } from './types'

export function applyEnvelope(envelope: NotificationEnvelope, queryClient: QueryClient): void {
    const update = (updater: (prev: Notification[] | undefined) => Notification[] | undefined) =>
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, updater)

    if (envelope.op === 'created') {
        update((prev) => (prev ? [envelope.notification, ...prev] : [envelope.notification]))
        return
    }
    if (envelope.op === 'updated') {
        const prev = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        if (!prev?.some((n) => n.id === envelope.notification.id)) {
            void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
            return
        }
        update((list) =>
            list?.map((n) => (n.id === envelope.notification.id ? envelope.notification : n)),
        )
        return
    }
    if (envelope.op === 'deleted') {
        const prev = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        if (!prev?.some((n) => n.id === envelope.id)) {
            void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
            return
        }
        update((list) => list?.filter((n) => n.id !== envelope.id))
        return
    }
    if (envelope.op === 'all-read') {
        update((prev) =>
            prev?.map((n) => (n.readAt === null ? { ...n, readAt: envelope.readAt } : n)),
        )
        return
    }
    void queryClient.invalidateQueries({ queryKey: NOTIFICATIONS_QUERY_KEY })
}
