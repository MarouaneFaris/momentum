import App from '@/App'
import * as Sentry from '@sentry/react'
import { createRoot } from 'react-dom/client'

const sentryDsn = import.meta.env.VITE_SENTRY_DSN as string | undefined

if (sentryDsn) {
    Sentry.init({
        dsn: sentryDsn,
        environment: import.meta.env.MODE,
    })
}

createRoot(document.getElementById('root')!).render(
    <Sentry.ErrorBoundary>
        <App />
    </Sentry.ErrorBoundary>,
)
