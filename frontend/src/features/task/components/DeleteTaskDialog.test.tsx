import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DeleteTaskDialog } from './DeleteTaskDialog'
import type { Task } from '../types'

const task: Task = {
    id: 'task-1',
    title: 'Fix the bug',
    status: 'todo',
    creatorId: 'user-1',
    assignee: null,
    createdAt: '2026-01-01T00:00:00Z',
}

describe('DeleteTaskDialog', () => {
    it('does not render when closed', () => {
        render(
            <DeleteTaskDialog
                open={false}
                onOpenChange={vi.fn()}
                task={task}
                isPending={false}
                onConfirm={vi.fn()}
            />,
        )
        expect(screen.queryByText('Delete task')).not.toBeInTheDocument()
    })

    it('shows task title in dialog when open', () => {
        render(
            <DeleteTaskDialog
                open={true}
                onOpenChange={vi.fn()}
                task={task}
                isPending={false}
                onConfirm={vi.fn()}
            />,
        )
        expect(screen.getByText('Delete task')).toBeInTheDocument()
        expect(screen.getByText('Fix the bug')).toBeInTheDocument()
    })

    it('calls onConfirm when Delete button clicked', async () => {
        const onConfirm = vi.fn()
        render(
            <DeleteTaskDialog
                open={true}
                onOpenChange={vi.fn()}
                task={task}
                isPending={false}
                onConfirm={onConfirm}
            />,
        )
        await userEvent.click(screen.getByRole('button', { name: /^delete$/i }))
        expect(onConfirm).toHaveBeenCalledTimes(1)
    })

    it('calls onOpenChange(false) when Cancel clicked', async () => {
        const onOpenChange = vi.fn()
        render(
            <DeleteTaskDialog
                open={true}
                onOpenChange={onOpenChange}
                task={task}
                isPending={false}
                onConfirm={vi.fn()}
            />,
        )
        await userEvent.click(screen.getByRole('button', { name: /cancel/i }))
        expect(onOpenChange).toHaveBeenCalledWith(false)
    })

    it('disables buttons while pending', () => {
        render(
            <DeleteTaskDialog
                open={true}
                onOpenChange={vi.fn()}
                task={task}
                isPending={true}
                onConfirm={vi.fn()}
            />,
        )
        expect(screen.getByRole('button', { name: /deleting/i })).toBeDisabled()
        expect(screen.getByRole('button', { name: /cancel/i })).toBeDisabled()
    })
})
