import { render, screen } from '@testing-library/react'
import VerifyEmailPage from '@/features/auth/components/VerifyEmailPage'
import { useVerifyEmailPage } from '@/features/auth/hooks/useVerifyEmailPage'
import type { VerifyState } from '@/features/auth/types'

vi.mock('react-router', () => ({
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

vi.mock('@/features/auth/hooks/useVerifyEmailPage')

function mockHook(
    state: VerifyState,
    overrides: Partial<ReturnType<typeof useVerifyEmailPage>> = {},
) {
    vi.mocked(useVerifyEmailPage).mockReturnValue({
        state,
        email: '',
        setEmail: vi.fn(),
        handleResend: vi.fn(),
        isResending: false,
        resendDone: false,
        emailError: null,
        ...overrides,
    })
}

describe('VerifyEmailPage', () => {
    it('shows spinner while verifying', () => {
        mockHook('verifying')
        render(<VerifyEmailPage />)
        expect(screen.getByText(/verifying your email/i)).toBeInTheDocument()
    })

    it('shows success state with sign-in link', () => {
        mockHook('success')
        render(<VerifyEmailPage />)
        expect(screen.getByText(/email verified/i)).toBeInTheDocument()
        expect(screen.getByRole('link', { name: /continue to sign in/i })).toHaveAttribute(
            'href',
            '/login',
        )
    })

    it('shows expired state with resend form', () => {
        mockHook('expired')
        render(<VerifyEmailPage />)
        expect(screen.getByText(/link expired/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/email address/i)).toBeInTheDocument()
        expect(
            screen.getByRole('button', { name: /resend verification email/i }),
        ).toBeInTheDocument()
    })

    it('shows invalid state with resend form', () => {
        mockHook('invalid')
        render(<VerifyEmailPage />)
        expect(screen.getByText(/invalid verification link/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/email address/i)).toBeInTheDocument()
        expect(screen.getByRole('button', { name: /send new link/i })).toBeInTheDocument()
    })

    it('shows no-token state with resend form', () => {
        mockHook('no-token')
        render(<VerifyEmailPage />)
        expect(screen.getByText(/invalid verification link/i)).toBeInTheDocument()
        expect(screen.getByLabelText(/email address/i)).toBeInTheDocument()
    })

    it('shows confirmation message after successful resend', () => {
        mockHook('expired', { resendDone: true })
        render(<VerifyEmailPage />)
        expect(screen.getByText(/check your inbox/i)).toBeInTheDocument()
        expect(screen.queryByLabelText(/email address/i)).not.toBeInTheDocument()
    })

    it('back-to-sign-in link present on error states', () => {
        mockHook('invalid')
        render(<VerifyEmailPage />)
        expect(screen.getByRole('link', { name: /back to sign in/i })).toHaveAttribute(
            'href',
            '/login',
        )
    })

    it('shows email error message when emailError is set', () => {
        mockHook('invalid', { emailError: 'Enter a valid email address' })
        render(<VerifyEmailPage />)
        expect(screen.getByText(/enter a valid email address/i)).toBeInTheDocument()
    })
})
