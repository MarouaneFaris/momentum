import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MobileMembersView } from './MobileMembersView'
import type { Member, InvitationOwnerView } from '../types'

const mockNavigate = vi.fn()

vi.mock('react-router', () => ({
    useNavigate: () => mockNavigate,
}))

vi.mock('@tanstack/react-query', () => ({
    useQueryClient: () => ({ invalidateQueries: vi.fn() }),
}))

vi.mock('sonner', () => ({
    toast: { success: vi.fn(), error: vi.fn() },
}))

vi.mock('@/components/BottomSheet', () => ({
    BottomSheet: ({
        children,
        open,
    }: {
        children: React.ReactNode
        open: boolean
        onOpenChange: (v: boolean) => void
        title?: string
    }) => (open ? <div role="dialog">{children}</div> : null),
}))

const mockHandleRemove = vi.fn()

vi.mock('../hooks/useMemberList', () => ({
    useMemberList: () => ({
        members,
        isLoading: false,
        isRemoving: false,
        isChanging: false,
        isLeaving: false,
        handleRemove: mockHandleRemove,
        handleRoleChange: vi.fn(),
        handleLeave: vi.fn(),
    }),
}))

const mockCancelMutate = vi.fn()
const mockUseWorkspaceInvitations = vi.fn()

vi.mock('../queries', () => ({
    useWorkspaceInvitations: (workspaceId: string, enabled?: boolean) => {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return mockUseWorkspaceInvitations(workspaceId, enabled)
    },
    useCancelInvitation: () => ({ mutate: mockCancelMutate, isPending: false }),
}))

vi.mock('../hooks/useInviteForm', () => ({
    useInviteForm: () => ({
        form: {
            register: () => ({}),
            control: {},
            handleSubmit: () => () => {},
            reset: vi.fn(),
            formState: { errors: {} },
        },
        isPending: false,
        onSubmit: vi.fn(),
    }),
}))

const members: Member[] = [
    {
        id: 'u1',
        name: 'Alex Johnson',
        email: 'alex@acme.com',
        role: 'owner',
        joinedAt: '2026-03-01T00:00:00Z',
    },
    {
        id: 'u2',
        name: 'Marie Laurent',
        email: 'marie@acme.com',
        role: 'member',
        joinedAt: '2026-04-12T00:00:00Z',
    },
    {
        id: 'u3',
        name: 'Bob K.',
        email: 'bob@client.com',
        role: 'guest',
        joinedAt: '2026-05-01T00:00:00Z',
    },
]

const pendingInvitations: InvitationOwnerView[] = [
    {
        id: 'inv-1',
        invitee: { id: 'u4', name: 'Alice Smith', email: 'alice@acme.com' },
        role: 'member',
        status: 'pending',
        expiresAt: new Date(Date.now() + 5 * 86_400_000).toISOString(),
        createdAt: '2026-06-07T00:00:00Z',
    },
]

const defaultProps = {
    workspaceId: 'ws-1',
    workspaceName: 'Acme workspace',
    isOwner: true,
    currentUserId: 'u1',
}

beforeEach(() => {
    vi.clearAllMocks()
    mockUseWorkspaceInvitations.mockReturnValue({ data: pendingInvitations })
})

describe('MobileMembersView — topbar', () => {
    it('shows "Invite" button for owner', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByRole('button', { name: /invite/i })).toBeInTheDocument()
    })

    it('hides "Invite" button for non-owner', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} />)
        expect(screen.queryByRole('button', { name: /invite/i })).not.toBeInTheDocument()
    })

    it('calls navigate(-1) when Back is clicked', async () => {
        render(<MobileMembersView {...defaultProps} />)
        await userEvent.click(screen.getByRole('button', { name: /back/i }))
        expect(mockNavigate).toHaveBeenCalledWith(-1)
    })
})

describe('MobileMembersView — members list', () => {
    it('renders all member names', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByText('Alex Johnson')).toBeInTheDocument()
        expect(screen.getByText('Marie Laurent')).toBeInTheDocument()
        expect(screen.getByText('Bob K.')).toBeInTheDocument()
    })

    it('renders correct role badges', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByText('Owner')).toBeInTheDocument()
        expect(screen.getByText('Member')).toBeInTheDocument()
        expect(screen.getByText('Guest')).toBeInTheDocument()
    })

    it('shows ⋯ menu for manageable members when current user is owner', () => {
        render(<MobileMembersView {...defaultProps} currentUserId="u1" />)
        // u2 (member) and u3 (guest) are manageable; u1 (owner/self) is not
        expect(screen.getAllByRole('button', { name: /member actions/i })).toHaveLength(2)
    })

    it('shows no ⋯ menus for non-owner view', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} currentUserId="u2" />)
        expect(screen.queryByRole('button', { name: /member actions/i })).not.toBeInTheDocument()
    })
})

describe('MobileMembersView — pending invitations', () => {
    it('shows pending invitations section for owner with pending invitations', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByText(/pending invitations/i)).toBeInTheDocument()
        expect(screen.getByText('alice@acme.com')).toBeInTheDocument()
    })

    it('hides pending invitations section for non-owner', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} />)
        expect(screen.queryByText(/pending invitations/i)).not.toBeInTheDocument()
    })

    it('hides pending invitations section when list is empty', () => {
        mockUseWorkspaceInvitations.mockReturnValueOnce({ data: [] })
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.queryByText(/pending invitations/i)).not.toBeInTheDocument()
    })
})

describe('MobileMembersView — cancel invitation', () => {
    it('fires cancel mutation when Cancel button is clicked on an invitation row', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const cancelBtn = screen.getByRole('button', { name: /cancel/i })
        await userEvent.click(cancelBtn)
        expect(mockCancelMutate).toHaveBeenCalledWith('inv-1', expect.any(Object))
    })
})

describe('MobileMembersView — remove member', () => {
    it('opens remove dialog with correct copy when ⋯ > Remove is clicked', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Remove'))
        expect(screen.getByRole('alertdialog')).toBeInTheDocument()
        expect(screen.getAllByText(/marie laurent/i).length).toBeGreaterThan(0)
        expect(screen.getAllByText(/acme workspace/i).length).toBeGreaterThan(0)
    })

    it('calls handleRemove when Remove member is confirmed', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Remove'))
        await userEvent.click(screen.getByRole('button', { name: /remove member/i }))
        expect(mockHandleRemove).toHaveBeenCalledWith('u2')
    })

    it('dismisses remove dialog on Cancel without calling handleRemove', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Remove'))
        await userEvent.click(screen.getByRole('button', { name: /^cancel$/i }))
        expect(mockHandleRemove).not.toHaveBeenCalled()
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument()
    })
})
