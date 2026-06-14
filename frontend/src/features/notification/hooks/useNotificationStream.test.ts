import { renderHook } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import React from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { AuthContext } from '@/contexts/auth/AuthContext'
import * as useMercureChannelModule from '@/lib/useMercureChannel'
import { NOTIFICATIONS_QUERY_KEY } from '../queries'
import type { Notification, NotificationEnvelope } from '../types'
import { useNotificationStream } from './useNotificationStream'

vi.mock('../queries', () => ({
    useNotificationList: vi.fn(() => ({ data: undefined })),
    NOTIFICATIONS_QUERY_KEY: ['notifications'] as const,
}))

let capturedOnMessage: ((envelope: NotificationEnvelope) => void) | null = null

vi.mock('@/lib/useMercureChannel', () => ({
    useMercureChannel: vi.fn((opts: { onMessage: (msg: NotificationEnvelope) => void }) => {
        capturedOnMessage = opts.onMessage
        return { status: 'idle' }
    }),
}))

const makeNotification = (overrides: Partial<Notification> = {}): Notification => ({
    id: 'n1',
    type: 'invitation_received',
    payload: { workspace_name: 'Acme', role_name: 'member' },
    readAt: null,
    createdAt: '2026-06-07T12:00:00Z',
    ...overrides,
})

const authValue = {
    user: { id: 'user-1', email: 'test@example.com', name: 'Test' },
    isLoading: false,
    isAuthenticated: true,
}

const fireMessage = (envelope: NotificationEnvelope) => {
    capturedOnMessage?.(envelope)
}

describe('useNotificationStream — policy', () => {
    let queryClient: QueryClient

    beforeEach(() => {
        capturedOnMessage = null
        queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [])
    })

    afterEach(() => {
        queryClient.clear()
        vi.clearAllMocks()
    })

    const wrapper = ({ children }: { children: React.ReactNode }) =>
        React.createElement(
            AuthContext.Provider,
            { value: authValue },
            React.createElement(QueryClientProvider, { client: queryClient }, children),
        )

    it('wires useMercureChannel with correct topic and enabled flag', () => {
        renderHook(() => useNotificationStream(), { wrapper })
        expect(vi.mocked(useMercureChannelModule.useMercureChannel)).toHaveBeenCalledWith(
            expect.objectContaining({
                topic: '/notifications/user-1',
                enabled: true,
            }),
        )
    })

    it('created op prepends notification to cache', () => {
        renderHook(() => useNotificationStream(), { wrapper })
        const n = makeNotification({ id: 'n-new' })
        fireMessage({ op: 'created', notification: n })
        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0]).toEqual(n)
    })

    it('updated op replaces matching notification in cache', () => {
        const existing = makeNotification({ id: 'n1', readAt: null })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [existing])
        renderHook(() => useNotificationStream(), { wrapper })
        const updated = { ...existing, readAt: '2026-06-07T13:00:00Z' }
        fireMessage({ op: 'updated', notification: updated })
        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0].readAt).toBe('2026-06-07T13:00:00Z')
    })

    it('deleted op removes notification from cache', () => {
        const n = makeNotification({ id: 'n-to-delete' })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n])
        renderHook(() => useNotificationStream(), { wrapper })
        fireMessage({ op: 'deleted', id: 'n-to-delete' })
        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached).toHaveLength(0)
    })

    it('all-read op stamps readAt on unread notifications', () => {
        const n1 = makeNotification({ id: 'n1', readAt: null })
        const n2 = makeNotification({ id: 'n2', readAt: '2026-01-01T00:00:00Z' })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n1, n2])
        renderHook(() => useNotificationStream(), { wrapper })
        const readAt = '2026-06-07T14:00:00Z'
        fireMessage({ op: 'all-read', readAt })
        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0].readAt).toBe(readAt)
        expect(cached?.[1].readAt).toBe('2026-01-01T00:00:00Z')
    })

    it('unknown op falls back to invalidateQueries', () => {
        const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries')
        renderHook(() => useNotificationStream(), { wrapper })
        fireMessage({ op: 'future-unknown-op' } as unknown as NotificationEnvelope)
        expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
    })
})
