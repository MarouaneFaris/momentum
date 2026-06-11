import { render, screen } from '@testing-library/react'
import { InvitationsTable } from './InvitationsTable'
import type { InvitationOwnerView } from '../types'

const mockHandleCancel = vi.fn()
const mockHandleResend = vi.fn()
const mockHandleReinvite = vi.fn()
const mockHandleDelete = vi.fn()

const invitations: InvitationOwnerView[] = [
    {
        id: 'inv-1',
        invitee: { id: 'u3', name: 'Alice Smith', email: 'alice@acme.com' },
        role: 'member',
        status: 'pending',
        expiresAt: '2026-06-14T00:00:00Z',
        createdAt: '2026-06-07T00:00:00Z',
    },
    {
        id: 'inv-2',
        invitee: { id: 'u4', name: 'Old Contact', email: 'old@company.com' },
        role: 'guest',
        status: 'expired',
        expiresAt: '2026-06-04T00:00:00Z',
        createdAt: '2026-05-28T00:00:00Z',
    },
]

const mockHook = vi.fn()

vi.mock('../hooks/useInvitationsTable', () => ({
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    useInvitationsTable: (...args: unknown[]) => mockHook(...args),
}))

const defaultHookResult = {
    invitations,
    isLoading: false,
    isCancelling: false,
    isResending: false,
    isReinviting: false,
    isDeleting: false,
    handleCancel: mockHandleCancel,
    handleResend: mockHandleResend,
    handleReinvite: mockHandleReinvite,
    handleDelete: mockHandleDelete,
}

beforeEach(() => {
    mockHook.mockReturnValue(defaultHookResult)
})

describe('InvitationsTable', () => {
    it('shows Resend and Cancel for pending invitation', () => {
        render(<InvitationsTable workspaceId="ws-1" />)
        expect(screen.getByRole('button', { name: /resend/i })).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument()
    })

    it('shows Reinvite and Delete for expired invitation', () => {
        render(<InvitationsTable workspaceId="ws-1" />)
        expect(screen.getByRole('button', { name: /reinvite/i })).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /delete/i })).toBeInTheDocument()
    })

    it('renders Pending and Expired status badges', () => {
        render(<InvitationsTable workspaceId="ws-1" />)
        expect(screen.getByText('Pending')).toBeInTheDocument()
        expect(screen.getByText('Expired')).toBeInTheDocument()
    })

    it('shows empty state when no invitations', () => {
        mockHook.mockReturnValueOnce({ ...defaultHookResult, invitations: [] })
        render(<InvitationsTable workspaceId="ws-1" />)
        expect(screen.getByText(/no invitations yet/i)).toBeInTheDocument()
    })
})
