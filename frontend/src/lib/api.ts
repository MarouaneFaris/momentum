type JsonResponse = {
    [key: string]: string | number | string[] | number[]
}

const baseUrl = import.meta.env.VITE_API_URL as string

const apiFetch = async (
    url: string,
    method: string | null = null,
    body: Record<string, unknown> | null = null,
): Promise<JsonResponse | null> => {
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

    const json = (await response.json()) as JsonResponse

    if (!response.ok) {
        throw new Error((json?.error as string) ?? 'Network response was not ok')
    }

    if (response.status === 204 || response.headers.get('Content-Length') === '0') {
        return null
    }

    return json
}

export default apiFetch
