import { Skeleton } from '@/components/ui/skeleton'

export function CardSkeleton() {
    return (
        <div className="flex flex-col gap-4 rounded-lg border p-6">
            <Skeleton className="h-5 w-40" />
            <div className="flex flex-col gap-2">
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-4/5" />
                <Skeleton className="h-4 w-3/5" />
            </div>
        </div>
    )
}
