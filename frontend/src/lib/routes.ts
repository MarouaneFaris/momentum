export const ROUTES = {
    login: '/login',
    logout: '/logout',
    me: '/me',
    register: '/register',
} as const

export type ApiRoute = (typeof ROUTES)[keyof typeof ROUTES]
