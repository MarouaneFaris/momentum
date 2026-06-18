import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { CheckCircle, Clock, Info, Loader2, Mail, XCircle } from 'lucide-react'
import { Link } from 'react-router'
import { useVerifyEmailPage } from '../hooks/useVerifyEmailPage'

export default function VerifyEmailPage() {
    const { state, register, handleResend, errors, isResending, resendDone } = useVerifyEmailPage()

    if (state === 'verifying') {
        return (
            <div className="flex w-full flex-col items-center gap-5 text-center">
                <div className="border-primary/20 bg-primary/10 flex h-[52px] w-[52px] items-center justify-center rounded-full border">
                    <Loader2 className="text-primary h-6 w-6 animate-spin" />
                </div>
                <div className="flex flex-col gap-1.5">
                    <p className="text-[17px] font-semibold tracking-tight">Verifying your email</p>
                    <p className="text-muted-foreground max-w-[280px] text-[13px] leading-relaxed">
                        Hang tight — this only takes a moment.
                    </p>
                </div>
            </div>
        )
    }

    if (state === 'success') {
        return (
            <div className="flex w-full flex-col items-center gap-5 text-center">
                <div className="flex h-[52px] w-[52px] items-center justify-center rounded-full border border-green-500/20 bg-green-500/10">
                    <CheckCircle className="h-6 w-6 text-green-500" />
                </div>
                <div className="flex flex-col gap-1.5">
                    <p className="text-[17px] font-semibold tracking-tight">Email verified</p>
                    <p className="text-muted-foreground max-w-[280px] text-[13px] leading-relaxed">
                        Your account is ready. Sign in to get started.
                    </p>
                </div>
                <div className="w-full">
                    <Button asChild className="w-full">
                        <Link to="/login">Continue to sign in</Link>
                    </Button>
                </div>
            </div>
        )
    }

    if (resendDone) {
        return (
            <div className="flex w-full flex-col items-center gap-5 text-center">
                <div className="border-primary/20 bg-primary/10 flex h-[52px] w-[52px] items-center justify-center rounded-full border">
                    <Mail className="text-primary h-6 w-6" />
                </div>
                <div className="flex flex-col gap-1.5">
                    <p className="text-[17px] font-semibold tracking-tight">Check your inbox</p>
                    <p className="text-muted-foreground max-w-[280px] text-[13px] leading-relaxed">
                        A new verification link is on its way. Check spam if it doesn't arrive
                        within a minute.
                    </p>
                </div>
                <div className="flex w-full flex-col gap-2">
                    <div className="text-muted-foreground bg-muted flex items-center gap-2 rounded-md border px-3 py-2 text-left text-[12px]">
                        <Info className="h-3.5 w-3.5 shrink-0" />
                        Didn't get it? Wait a moment before requesting another.
                    </div>
                    <Link
                        to="/login"
                        className="text-muted-foreground hover:text-foreground text-center text-[12px] underline underline-offset-3"
                    >
                        Back to sign in
                    </Link>
                </div>
            </div>
        )
    }

    const isExpired = state === 'expired'

    return (
        <div className="flex w-full flex-col items-center gap-5 text-center">
            <div
                className={`flex h-[52px] w-[52px] items-center justify-center rounded-full border ${
                    isExpired
                        ? 'border-amber-500/20 bg-amber-500/10'
                        : 'border-destructive/20 bg-destructive/10'
                }`}
            >
                {isExpired ? (
                    <Clock className="h-6 w-6 text-amber-500" />
                ) : (
                    <XCircle className="text-destructive h-6 w-6" />
                )}
            </div>
            <div className="flex flex-col gap-1.5">
                <p className="text-[17px] font-semibold tracking-tight">
                    {isExpired ? 'Link expired' : 'Invalid verification link'}
                </p>
                <p className="text-muted-foreground max-w-[280px] text-[13px] leading-relaxed">
                    {isExpired
                        ? 'Verification links expire after 24 hours. Enter your email to get a fresh one.'
                        : 'This link is malformed or has already been used. Enter your email to get a new one.'}
                </p>
            </div>
            <div className="flex w-full flex-col gap-2">
                <form onSubmit={handleResend} className="flex flex-col gap-2 pt-1">
                    <Label htmlFor="resend-email" className="text-left text-[12px]">
                        Email address
                    </Label>
                    <Input
                        id="resend-email"
                        type="email"
                        placeholder="you@example.com"
                        disabled={isResending}
                        {...register('email')}
                    />
                    {errors.email && (
                        <p className="text-destructive text-sm">{errors.email.message}</p>
                    )}
                    <Button type="submit" disabled={isResending} className="w-full">
                        {isResending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {isResending
                            ? 'Sending…'
                            : isExpired
                              ? 'Resend verification email'
                              : 'Send new link'}
                    </Button>
                </form>
                <Link
                    to="/login"
                    tabIndex={isResending ? -1 : undefined}
                    className={`text-center text-[12px] underline underline-offset-3 ${
                        isResending
                            ? 'text-muted-foreground pointer-events-none opacity-35'
                            : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    Back to sign in
                </Link>
            </div>
        </div>
    )
}
