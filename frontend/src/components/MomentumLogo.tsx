type Props = { size: 'sm' | 'lg' }

export function MomentumLogo({ size }: Props) {
    if (size === 'sm') {
        return (
            <svg
                viewBox="0 0 40 40"
                className="h-5 w-5 flex-shrink-0"
                role="img"
                aria-label="Momentum"
            >
                <g transform="translate(8, 5) skewX(-12)">
                    <rect
                        x="0"
                        y="0"
                        width="28"
                        height="9"
                        rx="1.5"
                        className="fill-primary"
                        opacity="0.25"
                    />
                    <rect
                        x="0"
                        y="13"
                        width="28"
                        height="9"
                        rx="1.5"
                        className="fill-primary"
                        opacity="0.6"
                    />
                    <rect x="0" y="26" width="28" height="9" rx="1.5" className="fill-primary" />
                </g>
            </svg>
        )
    }

    return (
        <svg
            viewBox="0 0 420 60"
            className="h-10 w-auto flex-shrink-0"
            role="img"
            aria-label="Momentum"
        >
            <g transform="translate(12, 6) skewX(-12)">
                <rect
                    x="0"
                    y="0"
                    width="38"
                    height="10"
                    rx="2"
                    className="fill-primary"
                    opacity="0.25"
                />
                <rect
                    x="0"
                    y="15"
                    width="38"
                    height="10"
                    rx="2"
                    className="fill-primary"
                    opacity="0.55"
                />
                <rect x="0" y="30" width="38" height="10" rx="2" className="fill-primary" />
            </g>
            <text
                x="66"
                y="44"
                fontSize="42"
                fontWeight="600"
                letterSpacing="-2"
                className="fill-foreground"
                style={{ fontFamily: 'inherit' }}
            >
                <tspan className="fill-primary">m</tspan>omentum
            </text>
        </svg>
    )
}
