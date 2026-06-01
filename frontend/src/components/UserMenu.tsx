import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useLogoutAction } from '@/features/auth/hooks/useLogoutAction'
import { useContext } from 'react'

function getInitials(name?: string | null, email?: string | null): string {
    if (name) {
        const parts = name.trim().split(/\s+/)
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
        }
        return name.slice(0, 2).toUpperCase()
    }
    if (!email) return 'U'
    const local = email.split('@')[0]
    return local.slice(0, 2).toUpperCase()
}

export function UserMenu() {
    const { user } = useContext(AuthContext)
    const { handleOnLogout } = useLogoutAction()
    const initials = getInitials(user?.name, user?.email)

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="h-7 w-7 rounded-full bg-primary/15 text-[10px] font-semibold text-primary border border-primary/30 ml-1 p-0"
                    aria-label="User menu"
                >
                    {initials}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleOnLogout}>Logout</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
