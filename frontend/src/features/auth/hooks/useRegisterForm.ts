import ApiError from '@/lib/ApiError'
import { copyFor } from '@/lib/errorCopy'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import z from 'zod'
import { useRegister } from '../queries'

const schema = z.object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.email(),
    password: z.string().min(12, 'Password must be at least 12 characters'),
})

const navigateToLogin = (navigate: ReturnType<typeof useNavigate>, message?: string) => {
    if (message) toast(message)
    void navigate('/login')
}

export const useRegisterForm = () => {
    const { mutate } = useRegister()
    const navigate = useNavigate()

    const {
        handleSubmit,
        register,
        formState: { errors },
    } = useForm({
        resolver: zodResolver(schema),
    })

    const handleOnSubmit = (e: React.SubmitEvent<HTMLFormElement>) => {
        void handleSubmit(({ name, email, password }) =>
            mutate(
                { name, email, password },
                {
                    onSuccess: (data) => {
                        navigateToLogin(navigate, data?.message)
                    },
                    onError: (error) => {
                        if (error instanceof ApiError) {
                            // AUTH_DUPLICATE_EMAIL is intentionally treated as success to prevent email enumeration
                            if (error.code === 'AUTH_DUPLICATE_EMAIL') {
                                navigateToLogin(navigate)
                                return
                            }
                            toast.error(copyFor(error.code))
                        }
                    },
                },
            ),
        )(e)
    }

    return { register, handleOnSubmit, errors }
}
