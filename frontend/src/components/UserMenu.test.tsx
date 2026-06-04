import { act, fireEvent, render, screen } from '@testing-library/react'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { UserMenu } from '@/components/UserMenu'

const mockLogout = vi.fn()

vi.mock('@/features/auth/hooks/useLogoutAction', () => ({
    useLogoutAction: () => ({ handleOnLogout: mockLogout }),
}))

const makeWrapper =
    (email: string | null = 'john.doe@example.com', name: string = '') =>
    ({ children }: { children: React.ReactNode }) => (
        <AuthContext.Provider
            value={{
                user: email ? { id: '00000000-0000-0000-0000-000000000001', email, name } : null,
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

    it('derives initials from full name', () => {
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com', 'John Doe') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('JD')
    })

    it('derives initials from single-word name', () => {
        render(<UserMenu />, { wrapper: makeWrapper('alice@example.com', 'Alice') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('AL')
    })

    it('falls back to email initials when name is empty', () => {
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com', '') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('JO')
    })

    it('renders fallback initials "U" when no user', () => {
        render(<UserMenu />, { wrapper: makeWrapper(null) })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('U')
    })

    it('derives initials from different email formats when no name', () => {
        render(<UserMenu />, { wrapper: makeWrapper('ab@example.com', '') })
        expect(screen.getByRole('button', { name: /user menu/i })).toHaveTextContent('AB')
    })

    it('clicking avatar opens dropdown with logout item', () => {
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com') })

        const button = screen.getByRole('button', { name: /user menu/i })
        act(() => {
            button.dispatchEvent(
                new MouseEvent('pointerdown', { bubbles: true, button: 0, ctrlKey: false }),
            )
        })

        expect(screen.getByText('Logout')).toBeInTheDocument()
    })

    it('clicking Logout invokes handleOnLogout', () => {
        render(<UserMenu />, { wrapper: makeWrapper('john.doe@example.com') })

        const button = screen.getByRole('button', { name: /user menu/i })
        act(() => {
            button.dispatchEvent(
                new MouseEvent('pointerdown', { bubbles: true, button: 0, ctrlKey: false }),
            )
        })
        fireEvent.click(screen.getByText('Logout'))

        expect(mockLogout).toHaveBeenCalledTimes(1)
    })
})
