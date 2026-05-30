import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'
import { Eye, EyeOff } from 'lucide-react'
import * as React from 'react'
import { useState } from 'react'

const PasswordInput = React.forwardRef<HTMLInputElement, React.ComponentProps<'input'>>(
    ({ className, ...props }, ref) => {
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
                    className="absolute right-0 top-0 h-full px-3"
                    onClick={() => setShow((v) => !v)}
                    tabIndex={-1}
                >
                    {show ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    <span className="sr-only">{show ? 'Hide password' : 'Show password'}</span>
                </Button>
            </div>
        )
    },
)

PasswordInput.displayName = 'PasswordInput'

export { PasswordInput }
