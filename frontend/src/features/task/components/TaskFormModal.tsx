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
import type { TaskFormValues } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    form: UseFormReturn<TaskFormValues>
    isPending: boolean
    onSubmit: (values: TaskFormValues) => void
    mode?: 'create' | 'edit'
}

export function TaskFormModal({
    open,
    onOpenChange,
    workspaceId,
    form,
    isPending,
    onSubmit,
    mode = 'create',
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
    const isEdit = mode === 'edit'

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
                    <DialogTitle>{isEdit ? 'Edit task' : 'Create task'}</DialogTitle>
                    <DialogDescription>
                        {isEdit ? 'Update task details.' : 'Enter details for your new task.'}
                    </DialogDescription>
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
                                <p className="text-destructive text-sm">{errors.title.message}</p>
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
                                    value={watch('assigneeId') || '__none__'}
                                    onValueChange={(v) =>
                                        setValue('assigneeId', v === '__none__' ? '' : v)
                                    }
                                >
                                    <SelectTrigger id="task-assignee">
                                        <SelectValue placeholder="Unassigned" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">Unassigned</SelectItem>
                                        {assignableMembers.map((m) => (
                                            <SelectItem key={m.id} value={m.id}>
                                                {m.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="grid gap-2">
                            <Label htmlFor="task-due-date">Due date</Label>
                            <Input id="task-due-date" type="date" {...register('dueDate')} />
                        </div>
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
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
