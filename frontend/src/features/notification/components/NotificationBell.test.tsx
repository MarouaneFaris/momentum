import { render, screen } from '@testing-library/react'
import { NotificationBell } from './NotificationBell'
import type { Notification } from '../types'

vi.mock('../hooks/useNotifications', () => ({
    useNotifications: vi.fn(),
}))

vi.mock('../queries', () => ({
    useMarkAllNotificationsRead: () => ({ mutate: vi.fn() }),
}))

vi.mock('../hooks/useNotificationActions', () => ({
    useMarkNotificationRead: () => ({ mutate: vi.fn() }),
    useDeleteNotification: () => ({ mutate: vi.fn() }),
}))

import { useNotifications } from '../hooks/useNotifications'

const makeNotification = (overrides: Partial<Notification> = {}): Notification => ({
    id: 'n1',
    type: 'invitation_received',
    payload: { workspace_name: 'Acme', role_name: 'member' },
    readAt: null,
    createdAt: new Date().toISOString(),
    ...overrides,
})

describe('NotificationBell', () => {
    it('shows unread dot badge when there are unread notifications', () => {
        vi.mocked(useNotifications).mockReturnValue({
            data: [makeNotification({ readAt: null })],
        } as never)

        render(<NotificationBell />)
        expect(screen.getByLabelText('Notifications').querySelector('span')).toBeTruthy()
    })

    it('does not show badge when all notifications are read', () => {
        vi.mocked(useNotifications).mockReturnValue({
            data: [makeNotification({ readAt: new Date().toISOString() })],
        } as never)

        render(<NotificationBell />)
        const button = screen.getByLabelText('Notifications')
        const badge = button.querySelector('.bg-blue-500')
        expect(badge).toBeNull()
    })

    it('does not show badge when no notifications', () => {
        vi.mocked(useNotifications).mockReturnValue({ data: [] } as never)

        render(<NotificationBell />)
        const button = screen.getByLabelText('Notifications')
        const badge = button.querySelector('.bg-blue-500')
        expect(badge).toBeNull()
    })
})
