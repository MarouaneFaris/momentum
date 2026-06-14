import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MobileMembersView } from './MobileMembersView'
import type { Member, InvitationOwnerView } from '../types'
import type { useInvitationsTable } from '../hooks/useInvitationsTable'

const mockNavigate = vi.fn()

vi.mock('react-router', () => ({
    useNavigate: () => mockNavigate,
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
const mockHandleRoleChange = vi.fn()
const mockHandleLeave = vi.fn()

vi.mock('../hooks/useMemberList', () => ({
    useMemberList: () => ({
        members,
        isLoading: false,
        isRemoving: false,
        isChanging: false,
        isLeaving: false,
        handleRemove: mockHandleRemove,
        handleRoleChange: mockHandleRoleChange,
        handleLeave: mockHandleLeave,
    }),
}))

const mockHandleCancel = vi.fn()
const mockHandleResend = vi.fn()
const mockHandleReinvite = vi.fn()
const mockHandleDelete = vi.fn()
const mockUseInvitationsTable = vi.fn<typeof useInvitationsTable>()

vi.mock('../hooks/useInvitationsTable', () => ({
    useInvitationsTable: (workspaceId: string) => mockUseInvitationsTable(workspaceId),
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

const expiredInvitations: InvitationOwnerView[] = [
    {
        id: 'inv-2',
        invitee: { id: 'u5', name: 'Old User', email: 'old@example.com' },
        role: 'guest',
        status: 'expired',
        expiresAt: '2026-05-01T00:00:00Z',
        createdAt: '2026-04-24T00:00:00Z',
    },
]

const defaultHookResult: ReturnType<typeof useInvitationsTable> = {
    invitations: [...pendingInvitations, ...expiredInvitations],
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

const defaultProps = {
    workspaceId: 'ws-1',
    workspaceName: 'Acme workspace',
    isOwner: true,
    currentUserId: 'u1',
}

beforeEach(() => {
    vi.clearAllMocks()
    mockUseInvitationsTable.mockReturnValue(defaultHookResult)
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

    it('shows ⋯ menu only for current user row in non-owner view', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} currentUserId="u2" />)
        expect(screen.getAllByRole('button', { name: /member actions/i })).toHaveLength(1)
    })
})

describe('MobileMembersView — change role', () => {
    it('opens role picker when "Change role" is clicked', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Change role'))
        const dialog = screen.getByRole('dialog')
        // Both role options shown inside the sheet
        expect(within(dialog).getByRole('button', { name: /^member$/i })).toBeInTheDocument()
        expect(within(dialog).getByRole('button', { name: /^guest$/i })).toBeInTheDocument()
    })

    it('calls handleRoleChange with new role when option selected', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Change role'))
        await userEvent.click(screen.getByRole('button', { name: /^guest$/i }))
        expect(mockHandleRoleChange).toHaveBeenCalledWith('u2', 'guest')
    })
})

describe('MobileMembersView — pending invitations', () => {
    it('shows pending invitations section for owner', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByText(/pending invitations/i)).toBeInTheDocument()
        expect(screen.getByText('alice@acme.com')).toBeInTheDocument()
    })

    it('hides pending invitations section for non-owner', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} />)
        expect(screen.queryByText(/pending invitations/i)).not.toBeInTheDocument()
    })

    it('hides pending invitations section when none pending', () => {
        mockUseInvitationsTable.mockReturnValueOnce({
            ...defaultHookResult,
            invitations: expiredInvitations,
        })
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.queryByText(/pending invitations/i)).not.toBeInTheDocument()
    })

    it('fires resend mutation from ⋯ > Resend on pending row', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [invBtn] = screen.getAllByRole('button', { name: /invitation actions/i })
        await userEvent.click(invBtn)
        await userEvent.click(screen.getByText('Resend'))
        expect(mockHandleResend).toHaveBeenCalledWith('inv-1')
    })

    it('fires cancel mutation from ⋯ > Cancel on pending row', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [invBtn] = screen.getAllByRole('button', { name: /invitation actions/i })
        await userEvent.click(invBtn)
        await userEvent.click(screen.getByRole('menuitem', { name: /cancel/i }))
        await userEvent.click(screen.getByRole('button', { name: /cancel invitation/i }))
        expect(mockHandleCancel).toHaveBeenCalledWith('inv-1')
    })
})

describe('MobileMembersView — expired invitations', () => {
    it('shows expired invitations section for owner', () => {
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.getByText(/expired invitations/i)).toBeInTheDocument()
        expect(screen.getByText('old@example.com')).toBeInTheDocument()
    })

    it('hides expired invitations section for non-owner', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} />)
        expect(screen.queryByText(/expired invitations/i)).not.toBeInTheDocument()
    })

    it('hides expired invitations section when none expired', () => {
        mockUseInvitationsTable.mockReturnValueOnce({
            ...defaultHookResult,
            invitations: pendingInvitations,
        })
        render(<MobileMembersView {...defaultProps} />)
        expect(screen.queryByText(/expired invitations/i)).not.toBeInTheDocument()
    })

    it('fires reinvite mutation from ⋯ > Reinvite on expired row', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const invBtns = screen.getAllByRole('button', { name: /invitation actions/i })
        // Second button is the expired invitation row
        await userEvent.click(invBtns[1])
        await userEvent.click(screen.getByText('Reinvite'))
        expect(mockHandleReinvite).toHaveBeenCalledWith('old@example.com', 'guest')
    })

    it('fires delete mutation from ⋯ > Delete on expired row', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const invBtns = screen.getAllByRole('button', { name: /invitation actions/i })
        await userEvent.click(invBtns[1])
        await userEvent.click(screen.getByText('Delete'))
        await userEvent.click(screen.getByRole('button', { name: /^delete$/i }))
        expect(mockHandleDelete).toHaveBeenCalledWith('inv-2')
    })
})

describe('MobileMembersView — leave workspace', () => {
    it('shows ⋯ menu for non-owner current user row', () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} currentUserId="u2" />)
        expect(screen.getByRole('button', { name: /member actions/i })).toBeInTheDocument()
    })

    it('does not show leave option for owner', () => {
        render(<MobileMembersView {...defaultProps} currentUserId="u1" />)
        expect(screen.queryByRole('menuitem', { name: /leave workspace/i })).not.toBeInTheDocument()
    })

    it('opens leave confirm dialog when Leave workspace is clicked in ⋯ menu', async () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} currentUserId="u2" />)
        await userEvent.click(screen.getByRole('button', { name: /member actions/i }))
        await userEvent.click(screen.getByRole('menuitem', { name: /leave workspace/i }))
        expect(screen.getByRole('dialog')).toBeInTheDocument()
        expect(screen.getByText(/leave workspace\?/i)).toBeInTheDocument()
    })

    it('calls handleLeave when leave is confirmed', async () => {
        render(<MobileMembersView {...defaultProps} isOwner={false} currentUserId="u2" />)
        await userEvent.click(screen.getByRole('button', { name: /member actions/i }))
        await userEvent.click(screen.getByRole('menuitem', { name: /leave workspace/i }))
        await userEvent.click(screen.getByRole('button', { name: /leave workspace/i }))
        expect(mockHandleLeave).toHaveBeenCalled()
    })
})

describe('MobileMembersView — remove member', () => {
    it('opens remove dialog with correct copy when ⋯ > Remove is clicked', async () => {
        render(<MobileMembersView {...defaultProps} />)
        const [menuBtn] = screen.getAllByRole('button', { name: /member actions/i })
        await userEvent.click(menuBtn)
        await userEvent.click(screen.getByText('Remove'))
        expect(screen.getByRole('dialog')).toBeInTheDocument()
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
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })
})
