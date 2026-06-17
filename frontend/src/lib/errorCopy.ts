const COPY: Record<string, string> = {
    AUTH_INVALID_CREDENTIALS: 'Email or password is incorrect.',
    AUTH_NOT_AUTHENTICATED: 'You must be logged in to do that.',
    WORKSPACE_NAME_TOO_LONG: 'Workspace name must be 64 characters or fewer.',
    WORKSPACE_NOT_FOUND: 'Workspace not found.',
    WORKSPACE_FORBIDDEN: "You don't have access to this workspace.",
    VALIDATION_FAILED: 'Please check the form and try again.',
    RATE_LIMITED: "You're doing that too fast. Try again in a moment.",
    INTERNAL_ERROR: 'Something went wrong on our end. Please try again.',
    EMAIL_TOKEN_INVALID: 'This verification link is invalid or has already been used.',
    EMAIL_TOKEN_EXPIRED: 'This verification link has expired.',
}

const GENERIC = 'Something went wrong. Please try again.'

export function copyFor(code: string): string {
    const copy = COPY[code]
    if (!copy && import.meta.env.DEV) {
        console.warn(`[errorCopy] missing copy for ${code}`)
    }
    return copy ?? GENERIC
}
