import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { useWorkspaceMembers } from '@/features/membership/queries'
import { cn } from '@/lib/utils'

export function AssigneeChips({
    workspaceId,
    value,
    onChange,
}: {
    workspaceId: string
    value: string
    onChange: (id: string) => void
}) {
    const { data: members } = useWorkspaceMembers(workspaceId)
    const assignable = members?.filter((m) => m.role !== 'guest') ?? []

    if (assignable.length === 0) return null

    return (
        <div className="flex flex-col gap-1.5">
            <Label>Assignee</Label>
            <div className="flex [scrollbar-width:none] gap-2 overflow-x-auto [&::-webkit-scrollbar]:hidden">
                <Button
                    type="button"
                    onClick={() => onChange('')}
                    className={cn(
                        'h-auto rounded-full border px-3 py-1 text-xs font-medium whitespace-nowrap transition-colors',
                        value === ''
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-muted text-muted-foreground',
                    )}
                >
                    Unassigned
                </Button>
                {assignable.map((m) => (
                    <Button
                        key={m.id}
                        type="button"
                        onClick={() => onChange(m.id)}
                        className={cn(
                            'h-auto rounded-full border px-3 py-1 text-xs font-medium whitespace-nowrap transition-colors',
                            value === m.id
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-muted text-muted-foreground',
                        )}
                    >
                        {m.name}
                    </Button>
                ))}
            </div>
        </div>
    )
}
