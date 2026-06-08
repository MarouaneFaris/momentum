import { QueryClient } from '@tanstack/react-query'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { applyEnvelope } from './applyEnvelope'
import { NOTIFICATIONS_QUERY_KEY } from './queries'
import type { Notification } from './types'

const makeNotification = (overrides: Partial<Notification> = {}): Notification => ({
    id: 'n1',
    type: 'invitation_received',
    payload: { workspace_name: 'Acme', role_name: 'member' },
    readAt: null,
    createdAt: '2026-06-08T10:00:00Z',
    ...overrides,
})

describe('applyEnvelope', () => {
    let queryClient: QueryClient
    let invalidateSpy: ReturnType<typeof vi.spyOn>

    beforeEach(() => {
        queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
        invalidateSpy = vi.spyOn(queryClient, 'invalidateQueries')
    })

    describe('created', () => {
        it('prepends notification when cache empty', () => {
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [])
            const n = makeNotification({ id: 'new' })
            applyEnvelope({ op: 'created', notification: n }, queryClient)
            const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
            expect(cached).toEqual([n])
        })

        it('prepends notification when cache populated', () => {
            const existing = makeNotification({ id: 'old' })
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [existing])
            const n = makeNotification({ id: 'new' })
            applyEnvelope({ op: 'created', notification: n }, queryClient)
            const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
            expect(cached?.[0].id).toBe('new')
        })
    })

    describe('updated — known id', () => {
        it('replaces matching notification', () => {
            const existing = makeNotification({ id: 'n1', readAt: null })
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [existing])
            const updated = { ...existing, readAt: '2026-06-08T11:00:00Z' }
            applyEnvelope({ op: 'updated', notification: updated }, queryClient)
            const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
            expect(cached?.[0].readAt).toBe('2026-06-08T11:00:00Z')
            expect(invalidateSpy).not.toHaveBeenCalled()
        })
    })

    describe('updated — unknown id', () => {
        it('invalidates when id not in cache', () => {
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [
                makeNotification({ id: 'other' }),
            ])
            const ghost = makeNotification({ id: 'ghost' })
            applyEnvelope({ op: 'updated', notification: ghost }, queryClient)
            expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
        })

        it('invalidates when cache is undefined', () => {
            applyEnvelope(
                { op: 'updated', notification: makeNotification({ id: 'ghost' }) },
                queryClient,
            )
            expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
        })
    })

    describe('deleted — known id', () => {
        it('removes matching notification', () => {
            const n = makeNotification({ id: 'n1' })
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n])
            applyEnvelope({ op: 'deleted', id: 'n1' }, queryClient)
            const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
            expect(cached).toHaveLength(0)
            expect(invalidateSpy).not.toHaveBeenCalled()
        })
    })

    describe('deleted — unknown id', () => {
        it('invalidates when id not in cache', () => {
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [
                makeNotification({ id: 'other' }),
            ])
            applyEnvelope({ op: 'deleted', id: 'ghost' }, queryClient)
            expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
        })

        it('invalidates when cache is undefined', () => {
            applyEnvelope({ op: 'deleted', id: 'ghost' }, queryClient)
            expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
        })
    })

    describe('all-read', () => {
        it('stamps readAt on unread notifications only', () => {
            const n1 = makeNotification({ id: 'n1', readAt: null })
            const n2 = makeNotification({ id: 'n2', readAt: '2026-01-01T00:00:00Z' })
            queryClient.setQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY, [n1, n2])
            applyEnvelope({ op: 'all-read', readAt: '2026-06-08T12:00:00Z' }, queryClient)
            const cached = queryClient.getQueryData<Notification[]>(NOTIFICATIONS_QUERY_KEY)
            expect(cached?.[0].readAt).toBe('2026-06-08T12:00:00Z')
            expect(cached?.[1].readAt).toBe('2026-01-01T00:00:00Z')
        })
    })

    describe('unknown op', () => {
        it('falls back to invalidateQueries', () => {
            applyEnvelope({ op: 'future-unknown' } as never, queryClient)
            expect(invalidateSpy).toHaveBeenCalledWith({ queryKey: NOTIFICATIONS_QUERY_KEY })
        })
    })
})
