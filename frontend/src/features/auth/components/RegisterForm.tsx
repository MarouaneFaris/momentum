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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useNavigate } from 'react-router'
import { useRegisterForm } from '../hooks/useRegisterForm'

export default function RegisterForm() {
    const navigate = useNavigate()
    const { register, handleOnSubmit } = useRegisterForm()

    return (
        <Card className="w-full max-w-sm">
            <CardHeader>
                <CardTitle>Create an account</CardTitle>
                <CardDescription>Enter your details to get started</CardDescription>
                <CardAction>
                    <Button variant="link" onClick={() => void navigate('/login')}>
                        Sign In
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <form id="registration-form" onSubmit={handleOnSubmit}>
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
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                required
                                {...register('password')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="confirm">Confirm password</Label>
                            <Input id="confirm" type="password" required {...register('confirm')} />
                        </div>
                    </div>
                </form>
            </CardContent>
            <CardFooter className="flex-col gap-2">
                <Button type="submit" className="w-full" form="registration-form">
                    Sign Up
                </Button>
            </CardFooter>
        </Card>
    )
}
