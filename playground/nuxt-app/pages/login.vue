<script setup lang="ts">
const { login, isAuthenticated } = useAuth()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

// Redirect if already authenticated
watch(isAuthenticated, (value) => {
  if (value) {
    navigateTo('/dashboard')
  }
}, { immediate: true })

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    await login(email.value, password.value)
    navigateTo('/dashboard')
  } catch (e: any) {
    error.value = e.data?.message || 'Login failed. Please check your credentials.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div data-testid="login-page">
    <h1 data-testid="login-title">Login</h1>

    <form @submit.prevent="handleLogin" data-testid="login-form">
      <div v-if="error" data-testid="login-error">
        {{ error }}
      </div>

      <div>
        <label for="email">Email</label>
        <input
          id="email"
          v-model="email"
          type="email"
          data-testid="email-input"
          placeholder="test@example.com"
          required
        />
      </div>

      <div>
        <label for="password">Password</label>
        <input
          id="password"
          v-model="password"
          type="password"
          data-testid="password-input"
          placeholder="password"
          required
        />
      </div>

      <button
        type="submit"
        data-testid="login-button"
        :disabled="loading"
      >
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>
    </form>

    <p>
      <NuxtLink to="/" data-testid="home-link">Back to Home</NuxtLink>
    </p>
  </div>
</template>
