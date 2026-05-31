export type LoginPayload = {
    email: string
    password: string
}

export type RegisterPayload = LoginPayload & { name: string }

export type LoginResponse = {
    id: number
    email: string
}

export type AuthResponse = LoginResponse

export type RegisterResponse = {
    message: string
}
