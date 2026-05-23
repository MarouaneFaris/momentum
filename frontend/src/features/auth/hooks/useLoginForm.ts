import ApiError from '@/lib/ApiError'
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

    const { handleSubmit, register } = useForm({
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
                        toast.error(error.message)
                    }
                },
            }),
        )(e)
    }

    return { register, handleOnSubmit }
}
