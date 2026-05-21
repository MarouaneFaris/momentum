export const ROUTES = {
    login: '/login',
} as const

export type ApiRoute = (typeof ROUTES)[keyof typeof ROUTES]
