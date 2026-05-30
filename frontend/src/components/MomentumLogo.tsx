type Props = { size: 'sm' | 'lg' }

export function MomentumLogo({ size }: Props) {
    const light = size === 'lg' ? '/logo-lockup-light.svg' : '/logo-icon-light.svg'
    const dark = size === 'lg' ? '/logo-lockup-dark.svg' : '/logo-icon-dark.svg'
    const cls = size === 'lg' ? 'h-10' : 'h-5'
    return (
        <>
            <img src={light} alt="Momentum" className={`${cls} w-auto dark:hidden`} />
            <img src={dark} alt="Momentum" className={`${cls} hidden w-auto dark:block`} />
        </>
    )
}
