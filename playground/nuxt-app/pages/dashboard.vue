<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const { user, logout, isAuthenticated } = useAuth()

const handleLogout = async () => {
  await logout()
  navigateTo('/login')
}

// Redirect if not authenticated (client-side check)
watch(isAuthenticated, (value) => {
  if (!value) {
    navigateTo('/login')
  }
})
</script>

<template>
  <div data-testid="dashboard-page">
    <h1 data-testid="dashboard-title">Dashboard</h1>

    <div data-testid="user-info">
      <p>Welcome, <span data-testid="user-name">{{ user?.name }}</span>!</p>
      <p>Email: <span data-testid="user-email">{{ user?.email }}</span></p>
    </div>

    <button
      @click="handleLogout"
      data-testid="logout-button"
    >
      Logout
    </button>

    <p>
      <NuxtLink to="/" data-testid="home-link">Back to Home</NuxtLink>
    </p>
  </div>
</template>
