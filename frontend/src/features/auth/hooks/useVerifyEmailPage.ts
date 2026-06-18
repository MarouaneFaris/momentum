import ApiError from '@/lib/ApiError'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router'
import z from 'zod'
import { useResendVerification, useVerifyEmail } from '../queries'
import type { VerifyState } from '../types'

export function useVerifyEmailPage() {
    const [searchParams] = useSearchParams()
    const token = searchParams.get('token') ?? ''
    const [state, setState] = useState<VerifyState>(token ? 'verifying' : 'no-token')
    const [email, setEmail] = useState('')
    const [emailError, setEmailError] = useState<string | null>(null)
    const [resendDone, setResendDone] = useState(false)

    const { mutate: verifyEmail } = useVerifyEmail()
    const { mutate: resendVerification, isPending: isResending } = useResendVerification()

    useEffect(() => {
        if (!token) return
        verifyEmail(
            { token },
            {
                onSuccess: () => setState('success'),
                onError: (err) => {
                    if (err instanceof ApiError && err.code === 'EMAIL_TOKEN_EXPIRED') {
                        setState('expired')
                    } else {
                        setState('invalid')
                    }
                },
            },
        )
    }, [token, verifyEmail])

    function handleSetEmail(value: string) {
        setEmail(value)
        if (emailError) setEmailError(null)
    }

    function handleResend() {
        if (!z.email().safeParse(email).success) {
            setEmailError('Enter a valid email address')
            return
        }
        resendVerification({ email }, { onSuccess: () => setResendDone(true) })
    }

    return {
        state,
        email,
        setEmail: handleSetEmail,
        handleResend,
        isResending,
        resendDone,
        emailError,
    }
}
