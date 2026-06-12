import { render, screen } from '@testing-library/react'
import LoginForm from '@/features/auth/components/LoginForm'

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
vi.mock('@/features/auth/hooks/useLoginForm', () => ({
    useLoginForm: () => ({ register: () => ({}), handleOnSubmit: vi.fn(), errors: {} }),
}))

describe('LoginForm', () => {
    it('renders email and password inputs', () => {
        render(<LoginForm />)
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/password/i)).toBeInTheDocument()
    })

    it('renders sign in button', () => {
        render(<LoginForm />)
        expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })

    it('renders welcome heading and register link', () => {
        render(<LoginForm />)
        expect(screen.getByText(/welcome back/i)).toBeInTheDocument()
        expect(screen.getByRole('link')).toHaveAttribute('href', '/register')
    })
})
