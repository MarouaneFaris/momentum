type Props = { size: 'sm' | 'lg' }

export function MomentumLogo({ size }: Props) {
    const cls = size === 'lg' ? 'h-10' : 'h-5'
    return (
        <>
            <img
                src="/logo-lockup-light.svg"
                alt="Momentum"
                className={`${cls} w-auto dark:hidden`}
            />
            <img
                src="/logo-lockup-dark.svg"
                alt="Momentum"
                className={`${cls} hidden w-auto dark:block`}
            />
        </>
    )
}
