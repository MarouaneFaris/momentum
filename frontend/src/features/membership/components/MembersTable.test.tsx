import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MembersTable } from './MembersTable'
import type { Member } from '../types'

const mockHandleRoleChange = vi.fn()
const mockHandleRemove = vi.fn()
const mockHandleLeave = vi.fn()

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
]

vi.mock('../hooks/useMemberList', () => ({
    useMemberList: () => ({
        members,
        isLoading: false,
        isChanging: false,
        isRemoving: false,
        isLeaving: false,
        handleRoleChange: mockHandleRoleChange,
        handleRemove: mockHandleRemove,
        handleLeave: mockHandleLeave,
    }),
}))

beforeEach(() => {
    vi.clearAllMocks()
})

describe('MembersTable', () => {
    it('shows role dropdown for manageable members when owner', () => {
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        expect(screen.getByRole('combobox')).toBeInTheDocument()
    })

    it('shows "You" label for owner row when owner is current user', () => {
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        expect(screen.getByText('You')).toBeInTheDocument()
    })

    it('shows Remove button for non-owner members when current user is owner', () => {
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        expect(screen.getByRole('button', { name: /remove/i })).toBeInTheDocument()
    })

    it('hides Remove button and role dropdown for non-owner view', () => {
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u2"
                isOwner={false}
                workspaceName="Acme Inc."
            />,
        )
        expect(screen.queryByRole('button', { name: /remove/i })).not.toBeInTheDocument()
        expect(screen.queryByRole('combobox')).not.toBeInTheDocument()
    })

    it('opens confirmation dialog when Remove is clicked', async () => {
        const user = userEvent.setup()
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        await user.click(screen.getByRole('button', { name: /remove/i }))
        expect(screen.getByRole('dialog')).toBeInTheDocument()
        expect(screen.getAllByText(/marie laurent/i).length).toBeGreaterThan(0)
        expect(screen.getByText(/acme inc\./i)).toBeInTheDocument()
    })

    it('calls handleRemove and closes dialog on confirm', async () => {
        const user = userEvent.setup()
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        await user.click(screen.getByRole('button', { name: /remove/i }))
        await user.click(screen.getByRole('button', { name: /remove member/i }))
        expect(mockHandleRemove).toHaveBeenCalledWith('u2')
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })

    it('dismisses dialog on cancel without calling handleRemove', async () => {
        const user = userEvent.setup()
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u1"
                isOwner={true}
                workspaceName="Acme Inc."
            />,
        )
        await user.click(screen.getByRole('button', { name: /remove/i }))
        const dialog = screen.getByRole('dialog')
        await user.click(within(dialog).getByRole('button', { name: /cancel/i }))
        expect(mockHandleRemove).not.toHaveBeenCalled()
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument()
    })

    it('shows Leave button for non-owner current user', () => {
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u2"
                isOwner={false}
                workspaceName="Acme Inc."
            />,
        )
        expect(screen.getByRole('button', { name: /leave/i })).toBeInTheDocument()
    })

    it('opens leave confirm dialog when Leave is clicked', async () => {
        const user = userEvent.setup()
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u2"
                isOwner={false}
                workspaceName="Acme Inc."
            />,
        )
        await user.click(screen.getByRole('button', { name: /leave/i }))
        expect(screen.getByRole('dialog')).toBeInTheDocument()
        expect(screen.getByText(/leave workspace\?/i)).toBeInTheDocument()
    })

    it('calls handleLeave on confirm', async () => {
        const user = userEvent.setup()
        render(
            <MembersTable
                workspaceId="ws-1"
                currentUserId="u2"
                isOwner={false}
                workspaceName="Acme Inc."
            />,
        )
        await user.click(screen.getByRole('button', { name: /^leave$/i }))
        await user.click(screen.getByRole('button', { name: /leave workspace/i }))
        expect(mockHandleLeave).toHaveBeenCalled()
    })
})
