<script setup lang="ts">
const { isAuthenticated, user } = useAuth()
</script>

<template>
  <div data-testid="home-page">
    <h1 data-testid="home-title">Welcome to Nuxt Auth Demo</h1>

    <div v-if="isAuthenticated" data-testid="authenticated-content">
      <p>Hello, {{ user?.name }}!</p>
      <NuxtLink to="/dashboard" data-testid="dashboard-link">Go to Dashboard</NuxtLink>
    </div>

    <div v-else data-testid="unauthenticated-content">
      <p>Please login to continue.</p>
      <NuxtLink to="/login" data-testid="login-link">Login</NuxtLink>
    </div>
  </div>
</template>
