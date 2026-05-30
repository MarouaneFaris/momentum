import { Button } from '@/components/ui/button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useNavigate } from 'react-router'
import { useLoginForm } from '../hooks/useLoginForm'

export default function LoginForm() {
    const navigate = useNavigate()
    const { register, handleOnSubmit } = useLoginForm()

    return (
        <Card className="w-full max-w-sm">
            <CardHeader>
                <CardTitle>Sign in to your account</CardTitle>
                <CardDescription>Enter your email below to sign in</CardDescription>
                <CardAction>
                    <Button variant="link" onClick={() => void navigate('/register')}>
                        Sign Up
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <form id="login-form" onSubmit={handleOnSubmit}>
                    <div className="flex flex-col gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="m@example.com"
                                required
                                {...register('email')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Password</Label>
                                <a
                                    href="#"
                                    className="ml-auto inline-block text-sm underline-offset-4 hover:underline"
                                >
                                    Forgot your password?
                                </a>
                            </div>
                            <PasswordInput id="password" required {...register('password')} />
                        </div>
                    </div>
                </form>
            </CardContent>
            <CardFooter className="flex-col gap-2">
                <Button type="submit" className="w-full" form="login-form">
                    Sign in
                </Button>
                <Button variant="outline" className="w-full">
                    Login with Google
                </Button>
            </CardFooter>
        </Card>
    )
}
