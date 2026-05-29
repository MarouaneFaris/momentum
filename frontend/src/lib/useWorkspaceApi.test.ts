import { renderHook } from '@testing-library/react'
import { describe, expect, it, vi, beforeEach } from 'vitest'
import { useWorkspaceApi } from './useWorkspaceApi'

vi.mock('react-router', () => ({
    useParams: vi.fn(),
}))

vi.mock('./api', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
    },
}))

import { useParams } from 'react-router'
import api from './api'

const mockUseParams = vi.mocked(useParams)

describe('useWorkspaceApi', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('throws when workspaceId param absent', () => {
        mockUseParams.mockReturnValue({})
        expect(() => renderHook(() => useWorkspaceApi())).toThrow(
            'useWorkspaceApi must be used inside a /workspaces/:id route',
        )
    })

    it('prepends /workspaces/:id to path on get', () => {
        mockUseParams.mockReturnValue({ id: '42' })
        const { result } = renderHook(() => useWorkspaceApi())
        void result.current.get('/members')
        expect(api.get).toHaveBeenCalledWith('/workspaces/42/members', undefined)
    })

    it('prepends /workspaces/:id to path on post', () => {
        mockUseParams.mockReturnValue({ id: '42' })
        const { result } = renderHook(() => useWorkspaceApi())
        void result.current.post('/members', { name: 'Alice' })
        expect(api.post).toHaveBeenCalledWith(
            '/workspaces/42/members',
            { name: 'Alice' },
            undefined,
        )
    })

    it('prepends /workspaces/:id to path on put', () => {
        mockUseParams.mockReturnValue({ id: '42' })
        const { result } = renderHook(() => useWorkspaceApi())
        void result.current.put('/members/1', { name: 'Bob' })
        expect(api.put).toHaveBeenCalledWith('/workspaces/42/members/1', { name: 'Bob' }, undefined)
    })

    it('prepends /workspaces/:id to path on patch', () => {
        mockUseParams.mockReturnValue({ id: '42' })
        const { result } = renderHook(() => useWorkspaceApi())
        void result.current.patch('/members/1', { name: 'Bob' })
        expect(api.patch).toHaveBeenCalledWith(
            '/workspaces/42/members/1',
            { name: 'Bob' },
            undefined,
        )
    })

    it('prepends /workspaces/:id to path on delete', () => {
        mockUseParams.mockReturnValue({ id: '42' })
        const { result } = renderHook(() => useWorkspaceApi())
        void result.current.delete('/members/1')
        expect(api.delete).toHaveBeenCalledWith('/workspaces/42/members/1', undefined)
    })

    it('exposes workspaceId', () => {
        mockUseParams.mockReturnValue({ id: '99' })
        const { result } = renderHook(() => useWorkspaceApi())
        expect(result.current.workspaceId).toBe('99')
    })
})
