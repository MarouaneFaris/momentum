import ApiError from '@/lib/ApiError'
import queryClient from '@/lib/queryClient'
import { toast } from 'sonner'
import { useLogout } from '../queries'

export const useLogoutAction = () => {
    const { mutate } = useLogout()

    const handleOnLogout = () => {
        mutate(undefined, {
            onSuccess: () => {
                void queryClient.invalidateQueries({ queryKey: ['me'] })
                toast('Bye!')
            },
            onError: (error) => {
                if (error instanceof ApiError) {
                    toast.error(error.message)
                }
            },
        })
    }

    return { handleOnLogout }
}
