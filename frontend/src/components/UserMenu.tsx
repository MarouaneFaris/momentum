import { ThemeToggleSwitch } from '@/components/ThemeToggleSwitch'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { getInitials } from '@/lib/initials'
import { AuthContext } from '@/contexts/auth/AuthContext'
import { useLogoutAction } from '@/features/auth/hooks/useLogoutAction'
import { useContext } from 'react'

export function UserMenu() {
    const { user } = useContext(AuthContext)
    const { handleOnLogout } = useLogoutAction()
    const initials = user?.name ? getInitials(user.name) : 'U'

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="bg-primary/15 text-primary border-primary/30 ml-1 h-7 w-7 rounded-full border p-0 text-[10px] font-semibold"
                    aria-label="User menu"
                >
                    {initials}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <div
                    className="flex items-center px-2 py-1.5 md:hidden"
                    onClick={(e) => e.stopPropagation()}
                >
                    <ThemeToggleSwitch />
                </div>
                <DropdownMenuSeparator className="md:hidden" />
                <DropdownMenuItem onClick={handleOnLogout}>Logout</DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
