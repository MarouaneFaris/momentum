export const ROUTES = {
    login: '/login',
    logout: '/logout',
    me: '/me',
} as const

export type ApiRoute = (typeof ROUTES)[keyof typeof ROUTES]
