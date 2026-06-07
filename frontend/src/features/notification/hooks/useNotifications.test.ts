import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { renderHook } from '@testing-library/react'
import React from 'react'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { NOTIFICATIONS_QUERY_KEY } from '../queries'
import type { Notification } from '../types'
import { useNotifications } from './useNotifications'

vi.mock('../queries', () => ({
    useNotificationList: vi.fn(() => ({ data: undefined })),
    NOTIFICATIONS_QUERY_KEY: ['notifications'] as const,
}))

let capturedOnMessage: ((event: MessageEvent) => void) | null = null
const mockEsClose = vi.fn()

class FakeEventSource {
    constructor() {}
    set onmessage(fn: (event: MessageEvent) => void) {
        capturedOnMessage = fn
    }
    get onmessage() {
        return capturedOnMessage ?? (() => {})
    }
    close = mockEsClose
}

const fireMessage = (data: unknown) => {
    capturedOnMessage?.({ data: JSON.stringify(data) } as MessageEvent)
}

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

describe('useNotifications envelope dispatch', () => {
    let queryClient: QueryClient

    beforeEach(() => {
        capturedOnMessage = null
        mockEsClose.mockClear()
        vi.stubGlobal('EventSource', FakeEventSource)
        vi.stubEnv('VITE_API_URL', 'https://api.example.com/api')

        queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [])
    })

    afterEach(() => {
        vi.unstubAllGlobals()
        vi.unstubAllEnvs()
        queryClient.clear()
    })

    const wrapper = ({ children }: { children: React.ReactNode }) =>
        React.createElement(
            AuthContext.Provider,
            { value: authValue },
            React.createElement(QueryClientProvider, { client: queryClient }, children),
        )

    it('created op prepends notification to cache', () => {
        renderHook(() => useNotifications(), { wrapper })

        const n = makeNotification({ id: 'n-new' })
        fireMessage({ op: 'created', notification: n })

        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0]).toEqual(n)
    })

    it('updated op replaces matching notification in cache', () => {
        const existing = makeNotification({ id: 'n1', readAt: null })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [existing])

        renderHook(() => useNotifications(), { wrapper })

        const updated = { ...existing, readAt: '2026-06-07T13:00:00Z' }
        fireMessage({ op: 'updated', notification: updated })

        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0].readAt).toBe('2026-06-07T13:00:00Z')
    })

    it('deleted op removes notification from cache', () => {
        const n = makeNotification({ id: 'n-to-delete' })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n])

        renderHook(() => useNotifications(), { wrapper })

        fireMessage({ op: 'deleted', id: 'n-to-delete' })

        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached).toHaveLength(0)
    })

    it('all-read op stamps readAt on unread notifications', () => {
        const n1 = makeNotification({ id: 'n1', readAt: null })
        const n2 = makeNotification({ id: 'n2', readAt: '2026-01-01T00:00:00Z' })
        queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n1, n2])

        renderHook(() => useNotifications(), { wrapper })

        const readAt = '2026-06-07T14:00:00Z'
        fireMessage({ op: 'all-read', readAt })

        const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
        expect(cached?.[0].readAt).toBe(readAt)
        expect(cached?.[1].readAt).toBe('2026-01-01T00:00:00Z')
    })

    it('unknown op falls back to invalidateQueries', () => {
        const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries')

        renderHook(() => useNotifications(), { wrapper })

        fireMessage({ op: 'future-unknown-op' })

        expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
    })

    it('malformed JSON falls back to invalidateQueries', () => {
        const invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries')

        renderHook(() => useNotifications(), { wrapper })

        capturedOnMessage?.({ data: 'not-json{{{' } as MessageEvent)

        expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
    })
})
