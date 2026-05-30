import queryClient from '@/lib/queryClient'
import { useLoginAs } from '../queries'

export const useDevLoginAction = () => {
    const { mutate: loginAs } = useLoginAs()

    const handleLoginAs = (email: string) => {
        const alreadyLoggedIn = queryClient.getQueryData(['me']) != null
        loginAs(email, {
            onSuccess: () => {
                if (alreadyLoggedIn) {
                    window.location.reload()
                } else {
                    void queryClient.invalidateQueries({ queryKey: ['me'] })
                }
            },
        })
    }

    return { handleLoginAs }
}
