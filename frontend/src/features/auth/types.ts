export type LoginPayload = {
    email: string
    password: string
}

export type RegisterPayload = LoginPayload & { name: string }

export type LoginResponse = {
    id: string
    email: string
    name: string
}

export type AuthResponse = LoginResponse

export type RegisterResponse = {
    message: string
}

export type VerifyEmailPayload = {
    token: string
}

export type VerifyEmailResponse = {
    message: string
}

export type ResendVerificationPayload = {
    email: string
}
