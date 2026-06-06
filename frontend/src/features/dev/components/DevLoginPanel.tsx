import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useDevLoginAction } from '@/features/dev/hooks/useDevLoginAction'
import { useDevUsers } from '@/features/dev/queries'

export default function DevLoginPanel() {
    const { data } = useDevUsers()
    const users = data ?? []
    const { handleLoginAs } = useDevLoginAction()

    if (!import.meta.env.DEV) return null

    return (
        <div className="fixed bottom-4 left-4 z-50">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="sm" className="text-xs">
                        Dev Login
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" className="w-auto">
                    {users.map((user) => (
                        <DropdownMenuItem key={user.id} onClick={() => handleLoginAs(user.email)}>
                            {user.email}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    )
}
