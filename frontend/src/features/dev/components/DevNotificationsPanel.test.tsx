import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import DevNotificationsPanel from './DevNotificationsPanel'

const mockTrigger = vi.fn()

vi.mock('@/features/dev/queries', () => ({
    useTriggerNotification: () => ({ mutate: mockTrigger }),
}))

const defaultProps = { onOpenChange: vi.fn() }

describe('DevNotificationsPanel', () => {
    beforeEach(() => {
        mockTrigger.mockClear()
    })

    it('renders trigger button', () => {
        render(<DevNotificationsPanel {...defaultProps} />)
        expect(screen.getByRole('button', { name: /notify/i })).toBeInTheDocument()
    })

    it('clicking a type fires mutate with correct type', async () => {
        const user = userEvent.setup()
        render(<DevNotificationsPanel onOpenChange={vi.fn()} />)

        await user.click(screen.getByRole('button', { name: /notify/i }))
        await user.click(screen.getByText('Task assigned to you'))

        expect(mockTrigger).toHaveBeenCalledWith('task_assigned_to_you')
    })

    it('dropdown lists all 8 notification types when open', async () => {
        const user = userEvent.setup()
        render(<DevNotificationsPanel onOpenChange={vi.fn()} />)

        await user.click(screen.getByRole('button', { name: /notify/i }))

        expect(screen.getByText('Task assigned to you')).toBeInTheDocument()
        expect(screen.getByText('Task assigned to member')).toBeInTheDocument()
        expect(screen.getByText('Your task status changed')).toBeInTheDocument()
        expect(screen.getByText("Member's task status changed")).toBeInTheDocument()
        expect(screen.getByText('Invitation received')).toBeInTheDocument()
        expect(screen.getByText('Invitation accepted')).toBeInTheDocument()
        expect(screen.getByText('Invitation declined')).toBeInTheDocument()
        expect(screen.getByText('Invitation cancelled')).toBeInTheDocument()
    })
})
