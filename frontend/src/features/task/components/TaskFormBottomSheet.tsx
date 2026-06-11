import { BottomSheet } from '@/components/BottomSheet'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { cn } from '@/lib/utils'
import type { UseFormReturn } from 'react-hook-form'
import type { TaskFormValues } from '../hooks/useTaskForm'
import type { TaskStatus } from '../types'
import { AssigneeChips } from './AssigneeChips'

const STATUS_OPTIONS: { value: TaskStatus; label: string }[] = [
    { value: 'todo', label: 'To do' },
    { value: 'in-progress', label: 'In progress' },
    { value: 'done', label: 'Done' },
]

export function TaskFormBottomSheet({
    open,
    onOpenChange,
    workspaceId,
    form,
    isPending,
    onSubmit,
    mode = 'create',
}: {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    form: UseFormReturn<TaskFormValues>
    isPending: boolean
    onSubmit: (values: TaskFormValues) => void
    mode?: 'create' | 'edit'
}) {
    const isEdit = mode === 'edit'
    const {
        register,
        handleSubmit,
        reset,
        setValue,
        watch,
        formState: { errors },
    } = form

    const assigneeId = watch('assigneeId') ?? ''
    const currentStatus = watch('status')

    return (
        <BottomSheet
            open={open}
            onOpenChange={(v) => {
                onOpenChange(v)
                if (!v) reset()
            }}
            title={isEdit ? 'Edit task' : 'Create task'}
        >
            <form
                id="mobile-task-form"
                onSubmit={(e) => void handleSubmit(onSubmit)(e)}
                className="flex flex-col gap-4 px-4 pt-4 pb-6"
            >
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="m-task-title">Title</Label>
                    <Input
                        id="m-task-title"
                        placeholder="Task title"
                        autoFocus
                        {...register('title')}
                    />
                    {errors.title && (
                        <p className="text-destructive text-sm">{errors.title.message}</p>
                    )}
                </div>

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="m-task-desc">Description</Label>
                    <Textarea
                        id="m-task-desc"
                        placeholder="Optional description"
                        rows={3}
                        {...register('description')}
                    />
                </div>

                {isEdit && (
                    <div className="flex flex-col gap-1.5">
                        <Label>Status</Label>
                        <div className="flex flex-wrap gap-2">
                            {STATUS_OPTIONS.map((opt) => (
                                <button
                                    key={opt.value}
                                    type="button"
                                    onClick={() => setValue('status', opt.value)}
                                    className={cn(
                                        'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                        currentStatus === opt.value
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border bg-muted text-muted-foreground',
                                    )}
                                >
                                    {opt.label}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <AssigneeChips
                    workspaceId={workspaceId}
                    value={assigneeId}
                    onChange={(id) => setValue('assigneeId', id)}
                />

                <div className="flex justify-end gap-2">
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
                    <Button type="submit" form="mobile-task-form" disabled={isPending}>
                        {isEdit ? 'Save' : 'Create'}
                    </Button>
                </div>
            </form>
        </BottomSheet>
    )
}
