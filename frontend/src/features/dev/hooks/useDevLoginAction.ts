import queryClient from '@/lib/queryClient'
import { useLoginAs } from '../queries'

export const useDevLoginAction = () => {
    const { mutate: loginAs } = useLoginAs()

    const handleLoginAs = (email: string) => {
        loginAs(email, {
            onSuccess: () => {
                void queryClient.invalidateQueries()
            },
        })
    }

    return { handleLoginAs }
}
