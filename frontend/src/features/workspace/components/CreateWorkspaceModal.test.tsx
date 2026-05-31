import { render, screen } from '@testing-library/react'
import { CreateWorkspaceModal } from './CreateWorkspaceModal'

vi.mock('../hooks/useCreateWorkspaceForm', () => ({
    useCreateWorkspaceForm: () => ({
        form: {
            register: () => ({}),
            handleSubmit: (fn: unknown) => fn,
            reset: vi.fn(),
            formState: { errors: {} },
        },
        isPending: false,
        onSubmit: vi.fn(),
    }),
}))

describe('CreateWorkspaceModal', () => {
    it('renders name input when open', () => {
        render(<CreateWorkspaceModal open={true} onOpenChange={vi.fn()} />)
        expect(screen.getByLabelText(/name/i)).toBeInTheDocument()
    })

    it('renders create and cancel buttons when open', () => {
        render(<CreateWorkspaceModal open={true} onOpenChange={vi.fn()} />)
        expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument()
    })

    it('renders nothing meaningful when closed', () => {
        render(<CreateWorkspaceModal open={false} onOpenChange={vi.fn()} />)
        expect(screen.queryByLabelText(/name/i)).not.toBeInTheDocument()
    })
})
