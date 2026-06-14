import { workspaceStorage } from '@/features/workspace/workspaceStorage'
import { useLoginAs } from '../queries'

export const useDevLoginAction = () => {
    const { mutate: loginAs } = useLoginAs()

    const handleLoginAs = (email: string) => {
        loginAs(email, {
            onSuccess: () => {
                workspaceStorage.clear()
                window.location.assign('/')
            },
        })
    }

    return { handleLoginAs }
}
