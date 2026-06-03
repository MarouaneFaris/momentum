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
import { useProjectForm } from '../hooks/useProjectForm'
import type { Project } from '../types'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    workspaceId: string
    project?: Project
}

export function ProjectFormModal({ open, onOpenChange, workspaceId, project }: Props) {
    const { form, isPending, onSubmit } = useProjectForm({
        workspaceId,
        project,
        onSuccess: () => onOpenChange(false),
    })
    const {
        register,
        handleSubmit,
        reset,
        setValue,
        watch,
        formState: { errors },
    } = form

    const isEdit = !!project
    const formId = 'project-form'

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{isEdit ? 'Edit project' : 'Create project'}</DialogTitle>
                    <DialogDescription>
                        {isEdit ? 'Update project details.' : 'Enter details for your new project.'}
                    </DialogDescription>
                </DialogHeader>
                <form id={formId} onSubmit={(e) => void handleSubmit(onSubmit)(e)}>
                    <div className="flex flex-col gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="project-name">Name</Label>
                            <Input
                                id="project-name"
                                placeholder="My project"
                                autoFocus
                                {...register('name')}
                            />
                            {errors.name && (
                                <p className="text-sm text-destructive">{errors.name.message}</p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="project-description">Description</Label>
                            <textarea
                                id="project-description"
                                placeholder="Optional description"
                                rows={3}
                                className="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                {...register('description')}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="project-status">Status</Label>
                            <Select
                                value={watch('status')}
                                onValueChange={(v) =>
                                    setValue('status', v as 'draft' | 'active' | 'archived')
                                }
                            >
                                <SelectTrigger id="project-status">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
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
                        {isEdit ? 'Save changes' : 'Create'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}
