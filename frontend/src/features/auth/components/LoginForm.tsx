import { PasswordInput } from '@/components/PasswordInput'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Link } from 'react-router'
import { useLoginForm } from '../hooks/useLoginForm'

export default function LoginForm() {
    const { register, handleOnSubmit } = useLoginForm()

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col gap-1">
                <h1 className="text-lg font-semibold tracking-tight">Welcome back</h1>
                <p className="text-sm text-muted-foreground">Sign in to your workspace</p>
            </div>

            <form id="login-form" onSubmit={handleOnSubmit} className="flex flex-col gap-5">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="you@company.com"
                        {...register('email')}
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput id="password" {...register('password')} />
                </div>
                <Button type="submit" className="w-full">
                    Sign in
                </Button>
            </form>

            <div className="flex items-center gap-2.5">
                <span className="h-px flex-1 bg-border" />
                <span className="text-xs text-muted-foreground">no account yet?</span>
                <span className="h-px flex-1 bg-border" />
            </div>

            <Button variant="outline" asChild className="w-full">
                <Link to="/register">Create account</Link>
            </Button>

            <Button variant="link" size="sm" type="button" className="w-full">
                Forgot password?
            </Button>
        </div>
    )
}
