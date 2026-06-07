import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'
import { Eye, EyeOff } from 'lucide-react'
import { useState } from 'react'

function PasswordInput({ className, ref, ...props }: React.ComponentProps<'input'>) {
    const [show, setShow] = useState(false)

    return (
        <div className="relative">
            <Input
                type={show ? 'text' : 'password'}
                className={cn('pr-10', className)}
                ref={ref}
                {...props}
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="absolute top-0 right-0 h-full px-3"
                onClick={() => setShow((v) => !v)}
                tabIndex={-1}
            >
                {show ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                <span className="sr-only">{show ? 'Hide password' : 'Show password'}</span>
            </Button>
        </div>
    )
}

export { PasswordInput }
