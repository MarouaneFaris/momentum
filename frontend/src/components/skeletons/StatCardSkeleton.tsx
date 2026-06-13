import { Skeleton } from '@/components/ui/skeleton'

export function StatCardSkeleton() {
    return (
        <div className="flex flex-col gap-3 rounded-lg border p-6">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-8 w-16" />
        </div>
    )
}
