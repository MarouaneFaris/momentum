import ApiError from '@/lib/ApiError'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useNavigate } from 'react-router'
import { toast } from 'sonner'
import z from 'zod'
import { useRegister } from '../queries'

const schema = z
    .object({
        email: z.email(),
        password: z.string().min(12),
        confirm: z.string(),
    })
    .refine((data) => data.password === data.confirm, {
        error: "Password don't match",
        path: ['confirm'],
    })

export const useRegisterForm = () => {
    const { mutate } = useRegister()
    const navigate = useNavigate()

    const { handleSubmit, register } = useForm({
        resolver: zodResolver(schema),
    })

    const handleOnSubmit = (e: React.SubmitEvent<HTMLFormElement>) => {
        void handleSubmit(({ email, password }) =>
            mutate(
                { email, password },
                {
                    onSuccess: (data) => {
                        if (data) {
                            toast(data.message, { position: 'top-center' })
                        }

                        void navigate('/login')
                    },
                    onError: (error) => {
                        if (error instanceof ApiError) {
                            toast.error(error.message, { position: 'top-center' })
                        }
                    },
                },
            ),
        )(e)
    }

    return { register, handleOnSubmit }
}
