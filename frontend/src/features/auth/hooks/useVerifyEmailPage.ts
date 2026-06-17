import ApiError from '@/lib/ApiError'
import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router'
import { useResendVerification, useVerifyEmail } from '../queries'
import type { VerifyState } from '../types'

export function useVerifyEmailPage() {
    const [searchParams] = useSearchParams()
    const token = searchParams.get('token') ?? ''
    const [state, setState] = useState<VerifyState>(token ? 'verifying' : 'no-token')
    const [email, setEmail] = useState('')
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

    function handleResend() {
        resendVerification({ email }, { onSuccess: () => setResendDone(true) })
    }

    return { state, email, setEmail, handleResend, isResending, resendDone }
}
