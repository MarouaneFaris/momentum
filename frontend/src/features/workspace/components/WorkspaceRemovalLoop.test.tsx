import { AuthContext } from '@/contexts/auth/AuthContext'
import api from '@/lib/api'
import ApiError from '@/lib/ApiError'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import type { ReactNode } from 'react'
import { MemoryRouter, Route, Routes, useLocation } from 'react-router'
import LandingPage from '@/pages/LandingPage'
import { workspaceStorage } from '../workspaceStorage'
import { WorkspaceGuard } from './WorkspaceGuard'

const REMOVED = { id: 'ws-removed', name: 'Removed', createdAt: '', role: 'member' as const }

// The user was removed from ws-removed: the per-workspace fetch now 403s, but the
// cached ['workspaces'] list (5-min staleTime) still contains it — so without a fix
// the landing page redirects straight back into the workspace the guard just rejected.
vi.mock('@/lib/api', () => ({
    default: { get: vi.fn() },
}))

const mockGet = vi.mocked(api.get)

const authValue = { user: null, isLoading: false, isAuthenticated: true }

function renderApp(client: QueryClient) {
    let renderCount = 0
    function LoopTripwire() {
        useLocation() // re-render on every redirect
        renderCount++
        if (renderCount > 20) throw new Error('REDIRECT_LOOP')
        return null
    }
    function Wrapper({ children }: { children: ReactNode }) {
        return (
            <QueryClientProvider client={client}>
                <AuthContext.Provider value={authValue}>{children}</AuthContext.Provider>
            </QueryClientProvider>
        )
    }
    return render(
        <Wrapper>
            <MemoryRouter initialEntries={['/workspaces/ws-removed/dashboard']}>
                <LoopTripwire />
                <Routes>
                    <Route path="/" element={<LandingPage />} />
                    <Route element={<WorkspaceGuard />}>
                        <Route path="/workspaces/:id/dashboard" element={<div>Dashboard</div>} />
                    </Route>
                </Routes>
            </MemoryRouter>
        </Wrapper>,
    )
}

describe('workspace removal redirect loop', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        workspaceStorage.write('ws-removed')
        // Per-workspace fetch is forbidden now that the user was removed.
        mockGet.mockRejectedValue(new ApiError('WORKSPACE_FORBIDDEN', 403, 'forbidden'))
    })

    it('does not ping-pong between guard and landing page on a 403', async () => {
        const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
        // Seed the stale list that still contains the removed workspace.
        client.setQueryData(['workspaces'], [REMOVED])

        renderApp(client)

        // A redirect loop trips LoopTripwire (throws REDIRECT_LOOP) before this resolves.
        // The fix evicts the dead workspace + clears storage, so we settle on the empty state.
        await screen.findByText('Welcome to Momentum', undefined, { timeout: 3000 })
        expect(screen.queryByText('Dashboard')).not.toBeInTheDocument()
        expect(workspaceStorage.read()).toBeNull()
    })
})
