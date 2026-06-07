import { PasswordInput } from '@/components/PasswordInput'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Link } from 'react-router'
import { useRegisterForm } from '../hooks/useRegisterForm'

export default function RegisterForm() {
    const { register, handleOnSubmit, errors } = useRegisterForm()

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col gap-1">
                <h1 className="text-lg font-semibold tracking-tight">Create an account</h1>
                <p className="text-muted-foreground text-sm">Get started with Momentum</p>
            </div>

            <form id="registration-form" onSubmit={handleOnSubmit} className="flex flex-col gap-5">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="name">Full name</Label>
                    <Input id="name" type="text" placeholder="Alex Johnson" {...register('name')} />
                    {errors.name && (
                        <p className="text-destructive text-sm">{errors.name.message}</p>
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="you@company.com"
                        {...register('email')}
                    />
                    {errors.email && (
                        <p className="text-destructive text-sm">{errors.email.message}</p>
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput id="password" {...register('password')} />
                    {errors.password && (
                        <p className="text-destructive text-sm">{errors.password.message}</p>
                    )}
                </div>
                <Button type="submit" className="w-full">
                    Create account
                </Button>
            </form>

            <div className="flex items-center gap-2.5">
                <span className="bg-border h-px flex-1" />
                <span className="text-muted-foreground text-xs">already have an account?</span>
                <span className="bg-border h-px flex-1" />
            </div>

            <Button variant="outline" asChild className="w-full">
                <Link to="/login">Sign in</Link>
            </Button>
        </div>
    )
}
