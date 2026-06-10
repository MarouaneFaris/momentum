import { useQueryClient } from '@tanstack/react-query'
import {
    NOTIFICATIONS_QUERY_KEY,
    useDeleteNotification,
    useMarkAllNotificationsRead,
    useMarkNotificationRead,
} from '../queries'
import type { Notification } from '../types'

export const useNotificationActions = () => {
    const queryClient = useQueryClient()
    const { mutate: markRead } = useMarkNotificationRead()
    const { mutate: deleteNotif } = useDeleteNotification()
    const { mutate: markAllRead } = useMarkAllNotificationsRead()

    const handleMarkRead = (id: string) => {
        const readAt = new Date().toISOString()
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.map((n) => (n.id === id ? { ...n, readAt } : n)),
        )
        markRead(id)
    }

    const handleDelete = (id: string) => {
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
            prev?.filter((n) => n.id !== id),
        )
        deleteNotif(id)
    }

    const handleMarkAllRead = () => {
        markAllRead(undefined, {
            onSuccess: () => {
                const readAt = new Date().toISOString()
                queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, (prev) =>
                    prev?.map((n) => (n.readAt === null ? { ...n, readAt } : n)),
                )
            },
        })
    }

    return { handleMarkRead, handleDelete, handleMarkAllRead }
}
