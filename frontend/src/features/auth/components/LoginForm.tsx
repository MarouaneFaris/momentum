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
import apiFetch from '@/lib/api'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import { z } from 'zod'

export default function LoginForm() {
    const schema = z.object({
        email: z.email(),
        password: z.string(),
    })

    const { handleSubmit, register } = useForm({
        resolver: zodResolver(schema),
    })

    const login = (credentials: z.input<typeof schema>) => {
        return apiFetch('/login', 'POST', credentials)
    }

    const mutation = useMutation({
        mutationFn: login,
        onSuccess: () => toast('Welcome back!', { position: 'top-center' }),
        onError: (error) => toast.error(error.message, { position: 'top-center' }),
    })

    const handleOnSubmit = (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault()
        void handleSubmit((data) => mutation.mutate(data))(e)
    }

    return (
        <Card className="w-full max-w-sm">
            <CardHeader>
                <CardTitle>Login to your account</CardTitle>
                <CardDescription>Enter your email below to login to your account</CardDescription>
                <CardAction>
                    <Button variant="link">Sign Up</Button>
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
                            <Input
                                id="password"
                                type="password"
                                required
                                {...register('password')}
                            />
                        </div>
                    </div>
                </form>
            </CardContent>
            <CardFooter className="flex-col gap-2">
                <Button type="submit" className="w-full" form="login-form">
                    Login
                </Button>
                <Button variant="outline" className="w-full">
                    Login with Google
                </Button>
            </CardFooter>
        </Card>
    )
}
