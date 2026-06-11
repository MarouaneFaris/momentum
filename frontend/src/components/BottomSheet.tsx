import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet'

type Props = {
    open: boolean
    onOpenChange: (open: boolean) => void
    title?: string
    children: React.ReactNode
}

export function BottomSheet({ open, onOpenChange, title, children }: Props) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="bottom"
                className="rounded-t-[18px] px-0 pt-0 pb-2"
                showCloseButton={false}
                aria-describedby={undefined}
            >
                <div className="flex justify-center pt-2.5 pb-1.5">
                    <div className="bg-border h-1 w-9 rounded-full" />
                </div>
                {title ? (
                    <SheetTitle className="border-border border-b px-4 pb-3 text-sm font-semibold">
                        {title}
                    </SheetTitle>
                ) : (
                    <SheetTitle className="sr-only">Bottom sheet</SheetTitle>
                )}
                {children}
            </SheetContent>
        </Sheet>
    )
}
