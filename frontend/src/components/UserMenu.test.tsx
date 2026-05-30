import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { UserMenu } from '@/components/UserMenu'

const mockLogout = vi.fn()

vi.mock('@/features/auth/hooks/useLogoutAction', () => ({
    useLogoutAction: () => ({ handleOnLogout: mockLogout }),
}))

const makeWrapper =
    (email: string | null = 'john.doe@example.com') =>
    ({ children }: { children: React.ReactNode }) => (
        <AuthContext.Provider
            value={{
                user: email ? { id: 1, email } : null,
                isLoading: false,
                isAuthenticated: email !== null,
            }}
        >
            {children}
        </AuthContext.Provider>
    )

describe('UserMenu', () => {
    beforeEach(() => {
        mockLogout.mockClear()
    })

    it('renders avatar button with correct initials from user email', () => {
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('JO')
    })

    it('renders fallback initials "U" when no user', () => {
        render(<UserMenu />, { wrapper: makeWrapper(null) })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('U')
    })

    it('derives initials from different email formats', () => {
        render(<UserMenu />, { wrapper: makeWrapper('ab@example.com') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('AB')
    })

    it('clicking avatar opens dropdown with logout item', async () => {
        const user = userEvent.setup()
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com') })

        const avatarButton = screen.getByRole('button', { name: /user menu/i })
        await user.click(avatarButton)

        expect(screen.getByText('Logout')).toBeInTheDocument()
    })

    it('clicking Logout invokes handleOnLogout', async () => {
        const user = userEvent.setup()
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com') })

        const avatarButton = screen.getByRole('button', { name: /user menu/i })
        await user.click(avatarButton)

        const logoutItem = screen.getByText('Logout')
        await user.click(logoutItem)

        expect(mockLogout).toHaveBeenCalledTimes(1)
    })
})
