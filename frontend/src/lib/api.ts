const baseUrl = import.meta.env.VITE_API_URL as string

const apiFetch = async (
    url: string,
    method: string | null = null,
    body: Record<string, unknown> | null = null,
) => {
    const response = await fetch(
        `${baseUrl}${url}`,
        Object.assign(body ? { body: JSON.stringify(body) } : {}, {
            method: method ?? 'GET',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
        }) as RequestInit,
    )

    if (!response.ok) {
        console.log(response)
        throw new Error('Network response was not ok')
    }

    if (response.status === 204 || response.headers.get('Content-Length') === '0') {
        return null
    }

    return response.json() as Promise<unknown>
}

export default apiFetch
