import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useWorkspaceMembers } from '@/features/membership/queries'
import type { UseFormReturn } from 'react-hook-form'
import type { TaskFormValues } from '../hooks/useTaskForm'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    form: UseFormReturn<TaskFormValues>
    isPending: boolean
    onSubmit: (values: TaskFormValues) => void
}

export function TaskFormModal({
    open,
    onOpenChange,
    workspaceId,
    form,
    isPending,
    onSubmit,
}: Props) {
    const { data: members } = useWorkspaceMembers(workspaceId)
    const assignableMembers = members?.filter((m) => m.role !== 'guest') ?? []

    const {
        register,
        handleSubmit,
        reset,
        setValue,
        watch,
        formState: { errors },
    } = form

    const formId = 'task-form'

    return (
        <Dialog
            open={open}
            onOpenChange={(v) => {
                onOpenChange(v)
                if (!v) reset()
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create task</DialogTitle>
                    <DialogDescription>Enter details for your new task.</DialogDescription>
                </DialogHeader>
                <form id={formId} onSubmit={(e) => void handleSubmit(onSubmit)(e)}>
                    <div className="flex flex-col gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="task-title">Title</Label>
                            <Input
                                id="task-title"
                                placeholder="Task title"
                                autoFocus
                                {...register('title')}
                            />
                            {errors.title && (
                                <p className="text-sm text-destructive">{errors.title.message}</p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="task-description">Description</Label>
                            <Textarea
                                id="task-description"
                                placeholder="Optional description"
                                rows={3}
                                {...register('description')}
                            />
                        </div>
                        {assignableMembers.length > 0 && (
                            <div className="grid gap-2">
                                <Label htmlFor="task-assignee">Assignee</Label>
                                <Select
                                    value={watch('assigneeId') ?? ''}
                                    onValueChange={(v) => setValue('assigneeId', v)}
                                >
                                    <SelectTrigger id="task-assignee">
                                        <SelectValue placeholder="Unassigned" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {assignableMembers.map((m) => (
                                            <SelectItem key={m.id} value={m.id}>
                                                {m.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                </form>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            onOpenChange(false)
                            reset()
                        }}
                    >
                        Cancel
                    </Button>
                    <Button type="submit" form={formId} disabled={isPending}>
                        Create
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
