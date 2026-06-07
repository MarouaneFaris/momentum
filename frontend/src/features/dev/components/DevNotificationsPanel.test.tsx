import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthContext } from '@/contexts/auth/AuthContext'
import DevNotificationsPanel from './DevNotificationsPanel'

const mockTrigger = vi.fn()

vi.mock('@/features/dev/queries', () => ({
    useTriggerNotification: () => ({ mutate: mockTrigger }),
}))

const authed = ({ children }: { children: React.ReactNode }) => (
    <AuthContext.Provider
        value={{
            user: { id: '1', email: 'a@b.com', name: 'A' },
            isLoading: false,
            isAuthenticated: true,
        }}
    >
        {children}
    </AuthContext.Provider>
)

describe('DevNotificationsPanel', () => {
    beforeEach(() => {
        mockTrigger.mockClear()
    })

    it('renders trigger button when dev and authenticated', () => {
        render(<DevNotificationsPanel />, { wrapper: authed })
        expect(screen.getByRole('button', { name: /dev notify/i })).toBeInTheDocument()
    })

    it('is hidden when import.meta.env.DEV is false', () => {
        vi.stubEnv('DEV', false)
        const { container } = render(<DevNotificationsPanel />, { wrapper: authed })
        expect(container).toBeEmptyDOMElement()
        vi.unstubAllEnvs()
    })

    it('is hidden when not authenticated', () => {
        const { container } = render(<DevNotificationsPanel />)
        expect(container).toBeEmptyDOMElement()
    })

    it('clicking a type fires POST with correct type', async () => {
        const user = userEvent.setup()
        render(<DevNotificationsPanel />, { wrapper: authed })

        await user.click(screen.getByRole('button', { name: /dev notify/i }))
        await user.click(screen.getByText('Task assigned to you'))

        expect(mockTrigger).toHaveBeenCalledWith('task_assigned_to_you')
    })

    it('dropdown lists all 7 notification types', async () => {
        const user = userEvent.setup()
        render(<DevNotificationsPanel />, { wrapper: authed })

        await user.click(screen.getByRole('button', { name: /dev notify/i }))

        expect(screen.getByText('Task assigned to you')).toBeInTheDocument()
        expect(screen.getByText('Task assigned to member')).toBeInTheDocument()
        expect(screen.getByText('Your task status changed')).toBeInTheDocument()
        expect(screen.getByText("Member's task status changed")).toBeInTheDocument()
        expect(screen.getByText('Invitation received')).toBeInTheDocument()
        expect(screen.getByText('Invitation accepted')).toBeInTheDocument()
        expect(screen.getByText('Invitation declined')).toBeInTheDocument()
    })
})
