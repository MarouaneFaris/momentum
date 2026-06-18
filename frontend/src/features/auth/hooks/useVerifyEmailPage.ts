import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useState } from 'react'
import type React from 'react'
import { useForm } from 'react-hook-form'
import { useSearchParams } from 'react-router'
import z from 'zod'
import { useResendVerification, useVerifyEmail } from '../queries'
import type { VerifyState } from '../types'

const resendSchema = z.object({
    email: z.email(),
})

type ResendFields = z.infer<typeof resendSchema>

export function useVerifyEmailPage() {
    const [searchParams] = useSearchParams()
    const token = searchParams.get('token') ?? ''
    const [state, setState] = useState<VerifyState>(token ? 'verifying' : 'no-token')
    const [resendDone, setResendDone] = useState(false)

    const { mutate: verifyEmail } = useVerifyEmail()
    const { mutate: resendVerification, isPending: isResending } = useResendVerification()

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<ResendFields>({
        resolver: zodResolver(resendSchema),
    })

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

    function handleResend(e: React.FormEvent<HTMLFormElement>) {
        void handleSubmit(({ email }: ResendFields) => {
            resendVerification({ email }, { onSuccess: () => setResendDone(true) })
        })(e)
    }

    return { state, register, handleResend, errors, isResending, resendDone }
}
