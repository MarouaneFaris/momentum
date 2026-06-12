import ApiError from '@/lib/ApiError'
import { copyFor } from '@/lib/errorCopy'
import queryClient from '@/lib/queryClient'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { toast } from 'sonner'
import z from 'zod'
import { useLogin } from '../queries'

const schema = z.object({
    email: z.email(),
    password: z.string(),
})

export const useLoginForm = () => {
    const { mutate } = useLogin()

    const { handleSubmit, register, setError } = useForm({
        resolver: zodResolver(schema),
    })

    const handleOnSubmit = (e: React.SubmitEvent<HTMLFormElement>) => {
        void handleSubmit((data) =>
            mutate(data, {
                onSuccess: () => {
                    void queryClient.invalidateQueries({ queryKey: ['me'] })
                    toast('Welcome back!')
                },
                onError: (error) => {
                    if (error instanceof ApiError) {
                        if (error.code === 'AUTH_INVALID_CREDENTIALS') {
                            setError('password', { message: copyFor(error.code) })
                            return
                        }
                        toast.error(copyFor(error.code))
                    }
                },
            }),
        )(e)
    }

    return { register, handleOnSubmit }
}
