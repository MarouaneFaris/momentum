import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useDevLoginAction } from '@/features/dev/hooks/useDevLoginAction'
import { useDevUsers } from '@/features/dev/queries'
import { LogIn } from 'lucide-react'

interface Props {
    onOpenChange: (open: boolean) => void
}

export default function DevLoginPanel({ onOpenChange }: Props) {
    const { data } = useDevUsers()
    const users = data ?? []
    const { handleLoginAs } = useDevLoginAction()

    return (
        <DropdownMenu onOpenChange={onOpenChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="dark:bg-background dark:hover:bg-muted flex items-center gap-1.5 text-xs shadow-md"
                >
                    <LogIn className="size-3" />
                    Login
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-auto">
                {users.map((user) => (
                    <DropdownMenuItem key={user.id} onClick={() => handleLoginAs(user.email)}>
                        {user.email}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
