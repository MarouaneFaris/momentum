export type LoginPayload = {
    email: string
    password: string
}

export type LoginResponse = {
    id: number
    email: string
}

export type AuthResponse = LoginResponse
