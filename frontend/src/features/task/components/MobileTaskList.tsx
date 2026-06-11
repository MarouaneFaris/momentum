import { Fab } from '@/components/Fab'
import { FilterChips } from '@/components/FilterChips'
import { Button } from '@/components/ui/button'
import { ClipboardList, Info, Plus } from 'lucide-react'
import type { Task, TaskStatus } from '../types'
import { TaskRow } from './TaskRow'

export type MobileFilter = 'all' | TaskStatus

const FILTER_OPTIONS = [
    { label: 'All', value: 'all' },
    { label: 'To do', value: 'todo' },
    { label: 'In progress', value: 'in-progress' },
    { label: 'Done', value: 'done' },
]

export function MobileTaskList({
    tasks,
    isEmpty,
    isGuest,
    filter,
    onFilterChange,
    onTaskTap,
    onNewTask,
}: {
    tasks: Task[]
    isEmpty: boolean
    isGuest: boolean
    filter: MobileFilter
    onFilterChange: (f: MobileFilter) => void
    onTaskTap: (taskId: string) => void
    onNewTask: () => void
}) {
    const filtered = filter === 'all' ? tasks : tasks.filter((t) => t.status === filter)

    return (
        <div className="flex flex-col gap-3 p-4">
            <h1 className="text-base font-semibold tracking-tight">Tasks</h1>

            {isGuest && (
                <div className="bg-muted border-border text-muted-foreground flex items-center gap-2 rounded-[var(--radius)] border px-3 py-2 text-xs">
                    <Info className="size-3.5 shrink-0" />
                    You&apos;re viewing as a Guest — read-only access.
                </div>
            )}

            <FilterChips
                options={FILTER_OPTIONS}
                value={filter}
                onChange={(v) => onFilterChange(v as MobileFilter)}
            />

            {isEmpty ? (
                <div className="flex flex-col items-center gap-3 px-6 py-16 text-center">
                    <ClipboardList className="text-border size-10" />
                    <div className="text-foreground text-sm font-medium">No tasks yet</div>
                    <div className="text-muted-foreground text-[13px]">
                        Create a task to start tracking work for this project.
                    </div>
                    {!isGuest && (
                        <Button size="lg" className="mt-1" onClick={onNewTask}>
                            <Plus />
                            New task
                        </Button>
                    )}
                </div>
            ) : filtered.length === 0 ? (
                <p className="text-muted-foreground py-8 text-center text-sm">
                    No tasks match this filter.
                </p>
            ) : (
                <div className="flex flex-col gap-2">
                    {filtered.map((task) => (
                        <TaskRow key={task.id} task={task} onClick={() => onTaskTap(task.id)} />
                    ))}
                </div>
            )}

            <Fab icon={Plus} onClick={onNewTask} hidden={isGuest} />
        </div>
    )
}
