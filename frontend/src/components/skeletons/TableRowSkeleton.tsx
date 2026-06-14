import { Skeleton } from '@/components/ui/skeleton'

export function TableRowSkeleton() {
    return (
        <tr className="border-b last:border-0">
            <td className="px-4 py-3">
                <div className="flex items-center gap-3">
                    <Skeleton className="size-8 shrink-0 rounded-full" />
                    <div className="flex flex-col gap-1.5">
                        <Skeleton className="h-3.5 w-28" />
                        <Skeleton className="h-3 w-36" />
                    </div>
                </div>
            </td>
            <td className="px-4 py-3">
                <Skeleton className="h-5 w-16" />
            </td>
            <td className="px-4 py-3">
                <Skeleton className="h-3.5 w-24" />
            </td>
            <td className="px-4 py-3 text-right">
                <Skeleton className="ml-auto h-7 w-16" />
            </td>
        </tr>
    )
}
