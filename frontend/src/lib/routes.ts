export const ROUTES = {
    login: '/login',
    logout: '/logout',
    me: '/me',
    register: '/register',
    workspaces: '/workspaces',
    verifyEmail: '/verify-email',
    resendVerification: '/resend-verification',
} as const

export type ApiRoute =
    | (typeof ROUTES)[keyof typeof ROUTES]
    | `/workspaces/${string}`
    | '/invitations'
    | `/invitations/${string}`
    | '/notifications'
    | `/notifications/${string}`
    | `/dev/${string}`
