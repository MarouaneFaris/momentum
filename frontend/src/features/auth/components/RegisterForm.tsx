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
                <p className="text-sm text-muted-foreground">Get started with Momentum</p>
            </div>

            <form id="registration-form" onSubmit={handleOnSubmit} className="flex flex-col gap-5">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="name">Full name</Label>
                    <Input id="name" type="text" placeholder="Alex Johnson" {...register('name')} />
                    {errors.name && (
                        <p className="text-sm text-destructive">{errors.name.message}</p>
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
                        <p className="text-sm text-destructive">{errors.email.message}</p>
                    )}
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput id="password" {...register('password')} />
                    {errors.password && (
                        <p className="text-sm text-destructive">{errors.password.message}</p>
                    )}
                </div>
                <Button type="submit" className="w-full">
                    Create account
                </Button>
            </form>

            <p className="text-center text-xs text-muted-foreground">
                Already have an account?{' '}
                <Link to="/login" className="text-primary hover:underline">
                    Sign in
                </Link>
            </p>
        </div>
    )
}
