import { BottomSheet } from '@/components/BottomSheet'
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
import { useIsMobile } from '@/hooks/useIsMobile'
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
    const isMobile = useIsMobile()

    const formFields = (
        <div className="flex flex-col gap-4">
            <div className="grid gap-2">
                <Label htmlFor="project-name">Name</Label>
                <Input id="project-name" placeholder="My project" autoFocus {...register('name')} />
                {errors.name && <p className="text-destructive text-sm">{errors.name.message}</p>}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="project-description">Description</Label>
                <Textarea
                    id="project-description"
                    placeholder="Optional description"
                    rows={3}
                    {...register('description')}
                />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="project-status">Status</Label>
                <Select
                    value={watch('status')}
                    onValueChange={(v) => setValue('status', v as 'draft' | 'active' | 'archived')}
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
    )

    if (isMobile) {
        return (
            <BottomSheet
                open={open}
                onOpenChange={onOpenChange}
                title={isEdit ? 'Edit project' : 'New project'}
            >
                <div className="flex flex-col gap-4 px-4 pt-3 pb-4">
                    <form id={formId} onSubmit={(e) => void handleSubmit(onSubmit)(e)}>
                        {formFields}
                    </form>
                    <Button type="submit" form={formId} disabled={isPending} className="w-full">
                        {isEdit ? 'Save changes' : 'Create project'}
                    </Button>
                </div>
            </BottomSheet>
        )
    }

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
                    {formFields}
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
