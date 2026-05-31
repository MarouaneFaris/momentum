import { render, screen } from '@testing-library/react'
import RegisterForm from '@/features/auth/components/RegisterForm'

vi.mock('react-router', () => ({
    useNavigate: () => vi.fn(),
    Link: ({
        to,
        children,
        className,
    }: {
        to: string
        children: React.ReactNode
        className?: string
    }) => (
        <a href={to} className={className}>
            {children}
        </a>
    ),
}))
vi.mock('@/features/auth/hooks/useRegisterForm', () => ({
    useRegisterForm: () => ({ register: () => ({}), handleOnSubmit: vi.fn(), errors: {} }),
}))

describe('RegisterForm', () => {
    it('renders name, email and password inputs', () => {
        render(<RegisterForm />)
        expect(screen.getByLabelText(/full name/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/^password/i)).toBeInTheDocument()
    })

    it('renders create account button', () => {
        render(<RegisterForm />)
        expect(screen.getByRole('button', { name: /create account/i })).toBeInTheDocument()
    })

    it('renders sign in link', () => {
        render(<RegisterForm />)
        expect(screen.getByRole('link', { name: /sign in/i })).toHaveAttribute('href', '/login')
    })
})
