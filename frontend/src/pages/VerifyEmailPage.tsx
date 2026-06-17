import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useVerifyEmailPage } from '@/features/auth/hooks/useVerifyEmailPage'
import { CheckCircle, Clock, Loader2, Mail, XCircle } from 'lucide-react'
import { Link } from 'react-router'

export default function VerifyEmailPage() {
    const { state, email, setEmail, handleResend, isResending, resendDone } = useVerifyEmailPage()

    if (state === 'verifying') {
        return (
            <div className="flex flex-col items-center gap-4 text-center">
                <Loader2 className="text-primary h-8 w-8 animate-spin" />
                <p className="text-muted-foreground">Verifying your email…</p>
            </div>
        )
    }

    if (state === 'success') {
        return (
            <Card className="mx-auto w-full max-w-md">
                <CardHeader className="items-center text-center">
                    <CheckCircle className="h-12 w-12 text-green-500" />
                    <CardTitle>Email verified!</CardTitle>
                    <CardDescription>Your account is ready. Sign in to continue.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button asChild className="w-full">
                        <Link to="/login">Sign in</Link>
                    </Button>
                </CardContent>
            </Card>
        )
    }

    const isExpired = state === 'expired'

    return (
        <Card className="mx-auto w-full max-w-md">
            <CardHeader className="items-center text-center">
                {isExpired ? (
                    <Clock className="h-12 w-12 text-amber-500" />
                ) : (
                    <XCircle className="text-destructive h-12 w-12" />
                )}
                <CardTitle>{isExpired ? 'Link expired' : 'Invalid link'}</CardTitle>
                <CardDescription>
                    {isExpired
                        ? 'Your verification link has expired. Enter your email to get a new one.'
                        : 'This link is invalid or has already been used. Enter your email to get a new one.'}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {resendDone ? (
                    <div className="text-muted-foreground flex items-center gap-2 text-sm">
                        <Mail className="h-4 w-4 shrink-0" />
                        <span>Check your inbox (and spam folder) for the new link.</span>
                    </div>
                ) : (
                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="resend-email">Email address</Label>
                            <Input
                                id="resend-email"
                                type="email"
                                placeholder="you@example.com"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                            />
                        </div>
                        <Button onClick={handleResend} disabled={isResending || !email}>
                            {isResending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Resend verification email
                        </Button>
                        <Link
                            to="/login"
                            className="text-muted-foreground hover:text-foreground text-center text-sm"
                        >
                            Back to sign in
                        </Link>
                    </div>
                )}
            </CardContent>
        </Card>
    )
}
