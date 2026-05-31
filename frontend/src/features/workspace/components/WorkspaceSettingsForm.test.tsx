import { render, screen } from '@testing-library/react'
import { WorkspaceSettingsForm } from './WorkspaceSettingsForm'
import type { Workspace } from '../types'

vi.mock('../hooks/useRenameWorkspaceForm', () => ({
    useRenameWorkspaceForm: () => ({
        form: {
            register: () => ({}),
            handleSubmit: (fn: unknown) => fn,
            formState: { errors: {} },
        },
        isPending: false,
        onSubmit: vi.fn(),
    }),
}))

const ownerWorkspace: Workspace = {
    id: 'ws-1',
    name: 'Acme Corp',
    createdAt: '2025-01-01T00:00:00Z',
    role: 'owner',
}

const memberWorkspace: Workspace = { ...ownerWorkspace, role: 'member' }

describe('WorkspaceSettingsForm', () => {
    it('renders editable name input for owner', () => {
        render(<WorkspaceSettingsForm workspace={ownerWorkspace} />)
        expect(screen.getByLabelText(/workspace name/i)).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /save changes/i })).toBeInTheDocument()
    })

    it('renders read-only name and role for non-owner', () => {
        render(<WorkspaceSettingsForm workspace={memberWorkspace} />)
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
        expect(screen.getByText('member')).toBeInTheDocument()
        expect(screen.queryByRole('button')).not.toBeInTheDocument()
    })
})
