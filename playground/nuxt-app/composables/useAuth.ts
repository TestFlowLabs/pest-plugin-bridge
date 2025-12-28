interface User {
  id: number
  name: string
  email: string
}

function getCookie(name: string): string | undefined {
  if (typeof document === 'undefined') return undefined
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    const cookiePart = parts.pop()
    if (cookiePart) {
      return decodeURIComponent(cookiePart.split(';').shift() || '')
    }
  }
  return undefined
}

export const useAuth = () => {
  const config = useRuntimeConfig()
  const user = useState<User | null>('user', () => null)
  const isAuthenticated = computed(() => !!user.value)

  const getCsrfCookie = async () => {
    await $fetch(`${config.public.apiBase}/sanctum/csrf-cookie`, {
      credentials: 'include',
    })
  }

  const login = async (email: string, password: string) => {
    await getCsrfCookie()

    const xsrfToken = getCookie('XSRF-TOKEN')

    const response = await $fetch<{ user: User }>(`${config.public.apiBase}/api/login`, {
      method: 'POST',
      body: { email, password },
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      },
    })

    user.value = response.user
    return response
  }

  const logout = async () => {
    const xsrfToken = getCookie('XSRF-TOKEN')

    await $fetch(`${config.public.apiBase}/api/logout`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        ...(xsrfToken ? { 'X-XSRF-TOKEN': xsrfToken } : {}),
      },
    })

    user.value = null
  }

  const fetchUser = async () => {
    try {
      const response = await $fetch<User>(`${config.public.apiBase}/api/user`, {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
        },
      })
      user.value = response
    } catch {
      user.value = null
    }
  }

  return {
    user,
    isAuthenticated,
    login,
    logout,
    fetchUser,
  }
}
