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

export const useRegisterForm = () => {
    const { mutate } = useRegister()
    const navigate = useNavigate()

    const {
        handleSubmit,
        register,
        setError,
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
                        if (data) {
                            toast(data.message)
                        }

                        void navigate('/login')
                    },
                    onError: (error) => {
                        if (error instanceof ApiError) {
                            if (error.code === 'AUTH_DUPLICATE_EMAIL') {
                                setError('email', { message: copyFor(error.code) })
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
