import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import type React from 'react'
import { useEffect, useState } from 'react'
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

    const { mutateAsync: verifyEmail } = useVerifyEmail()
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

        async function run() {
            const minDelay = new Promise<void>((res) => setTimeout(res, 1000))
            try {
                await Promise.all([verifyEmail({ token }), minDelay])
                setState('success')
            } catch (err) {
                await minDelay
                if (err instanceof ApiError && err.code === 'EMAIL_TOKEN_EXPIRED') {
                    setState('expired')
                } else {
                    setState('invalid')
                }
            }
        }

        void run()
    }, [token, verifyEmail])

    function handleResend(e: React.FormEvent<HTMLFormElement>) {
        void handleSubmit(({ email }: ResendFields) => {
            resendVerification({ email }, { onSuccess: () => setResendDone(true) })
        })(e)
    }

    return { state, register, handleResend, errors, isResending, resendDone }
}
