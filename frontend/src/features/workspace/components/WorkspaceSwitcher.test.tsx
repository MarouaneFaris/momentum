import { render, screen } from '@testing-library/react'
import { WorkspaceSwitcher } from './WorkspaceSwitcher'

const mockState = {
    current: { id: 'ws-1', name: 'Acme Corp', createdAt: '', role: 'owner' as const },
    workspaces: [
        { id: 'ws-1', name: 'Acme Corp', createdAt: '', role: 'owner' as const },
        { id: 'ws-2', name: 'Beta Co', createdAt: '', role: 'member' as const },
    ],
    isModalOpen: false,
    setIsModalOpen: vi.fn(),
    handleSelect: vi.fn(),
}

vi.mock('../hooks/useWorkspaceSwitcher', () => ({
    useWorkspaceSwitcher: () => mockState,
}))

vi.mock('./CreateWorkspaceModal', () => ({
    CreateWorkspaceModal: () => null,
}))

describe('WorkspaceSwitcher', () => {
    it('renders current workspace name', () => {
        render(<WorkspaceSwitcher />)
        expect(screen.getByText('Acme Corp')).toBeInTheDocument()
    })

    it('renders null when no workspaces', () => {
        const saved = mockState.workspaces
        mockState.workspaces = []
        const { container } = render(<WorkspaceSwitcher />)
        expect(container).toBeEmptyDOMElement()
        mockState.workspaces = saved
    })
})
