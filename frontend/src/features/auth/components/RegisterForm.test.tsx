import { render, screen } from '@testing-library/react'
import RegisterForm from '@/features/auth/components/RegisterForm'

vi.mock('react-router', () => ({ useNavigate: () => vi.fn() }))
vi.mock('@/features/auth/hooks/useRegisterForm', () => ({
    useRegisterForm: () => ({ register: () => ({}), handleOnSubmit: vi.fn(), errors: {} }),
}))

describe('RegisterForm', () => {
    it('renders email, password and confirm inputs', () => {
        render(<RegisterForm />)
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/^password/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/confirm/i)).toBeInTheDocument()
    })

    it('renders sign up button', () => {
        render(<RegisterForm />)
        expect(screen.getByRole('button', { name: /sign up/i })).toBeInTheDocument()
    })

    it('shows password mismatch error when errors are populated', () => {
        vi.mock('@/features/auth/hooks/useRegisterForm', () => ({
            useRegisterForm: () => ({
                register: () => ({}),
                handleOnSubmit: vi.fn(),
                errors: { confirm: { message: "Password don't match" } },
            }),
        }))
        render(<RegisterForm />)
    })
})
