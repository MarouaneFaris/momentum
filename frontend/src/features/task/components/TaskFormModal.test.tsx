import { render, screen } from '@testing-library/react'
import { TaskFormModal } from './TaskFormModal'

vi.mock('@/features/membership/queries', () => ({
    useWorkspaceMembers: () => ({ data: [] }),
}))

const makeForm = (defaultValues = { title: '', description: '', assigneeId: '' }) => ({
    register: () => ({}),
    handleSubmit: (fn: unknown) => fn,
    reset: vi.fn(),
    setValue: vi.fn(),
    watch: () => '',
    formState: { errors: {} },
    defaultValues,
})

describe('TaskFormModal', () => {
    it('shows "Create task" title in create mode', () => {
        render(
            <TaskFormModal
                open={true}
                onOpenChange={vi.fn()}
                workspaceId="ws-1"
                form={makeForm() as never}
                isPending={false}
                onSubmit={vi.fn()}
            />,
        )
        expect(screen.getByText('Create task')).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /create/i })).toBeInTheDocument()
    })

    it('shows "Edit task" title and "Save" button in edit mode', () => {
        render(
            <TaskFormModal
                open={true}
                onOpenChange={vi.fn()}
                workspaceId="ws-1"
                form={
                    makeForm({ title: 'Existing title', description: '', assigneeId: '' }) as never
                }
                isPending={false}
                onSubmit={vi.fn()}
                mode="edit"
            />,
        )
        expect(screen.getByText('Edit task')).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /save/i })).toBeInTheDocument()
    })

    it('does not render content when closed', () => {
        render(
            <TaskFormModal
                open={false}
                onOpenChange={vi.fn()}
                workspaceId="ws-1"
                form={makeForm() as never}
                isPending={false}
                onSubmit={vi.fn()}
            />,
        )
        expect(screen.queryByText('Create task')).not.toBeInTheDocument()
    })

    it('disables submit button while pending', () => {
        render(
            <TaskFormModal
                open={true}
                onOpenChange={vi.fn()}
                workspaceId="ws-1"
                form={makeForm() as never}
                isPending={true}
                onSubmit={vi.fn()}
            />,
        )
        expect(screen.getByRole('button', { name: /create/i })).toBeDisabled()
    })
})
