import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DeleteWorkspaceZone } from './DeleteWorkspaceZone'
import type { Workspace } from '../types'

const mockSetConfirmation = vi.fn()
const mockHandleDelete = vi.fn()

vi.mock('../hooks/useDeleteWorkspaceAction', () => ({
    useDeleteWorkspaceAction: () => ({
        confirmation: '',
        setConfirmation: mockSetConfirmation,
        isConfirmed: false,
        isPending: false,
        handleDelete: mockHandleDelete,
    }),
}))

const workspace: Workspace = {
    id: 'ws-1',
    name: 'Acme Corp',
    createdAt: '2025-01-01T00:00:00Z',
    role: 'owner',
}

describe('DeleteWorkspaceZone', () => {
    it('renders workspace name in warning text', () => {
        render(<DeleteWorkspaceZone workspace={workspace} />)
        expect(screen.getAllByText(/acme corp/i).length).toBeGreaterThan(0)
    })

    it('renders confirmation input', () => {
        render(<DeleteWorkspaceZone workspace={workspace} />)
        expect(screen.getByLabelText(/type/i)).toBeInTheDocument()
    })

    it('delete button is disabled when not confirmed', () => {
        render(<DeleteWorkspaceZone workspace={workspace} />)
        expect(screen.getByRole('button', { name: /delete workspace/i })).toBeDisabled()
    })

    it('calls setConfirmation when input changes', async () => {
        render(<DeleteWorkspaceZone workspace={workspace} />)
        const input = screen.getByLabelText(/type/i)
        await userEvent.type(input, 'A')
        expect(mockSetConfirmation).toHaveBeenCalled()
    })
})
