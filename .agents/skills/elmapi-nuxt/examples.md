# Nuxt Examples

## `useFetch` from Pages

```vue
<script setup lang="ts">
const { data: posts } = await useFetch('/api/posts');
</script>

<template>
  <ul>
    <li v-for="post in posts?.data" :key="post.uuid">
      {{ post.fields?.title }}
    </li>
  </ul>
</template>
```

## Route Middleware

```typescript
export default defineNuxtRouteMiddleware(() => {
  if (import.meta.server) return;
  const token = localStorage.getItem('noma_access_token');
  if (!token) return navigateTo('/login');
});
```

## Basic Pinia Auth Store

```typescript
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', () => {
  const { $elmapi } = useNuxtApp();
  const user = ref<any>(null);

  async function signIn(email: string, password: string) {
    await $elmapi.signInWithPassword({ email, password });
    user.value = await $elmapi.me();
  }

  async function signOut() {
    await $elmapi.signOut();
    user.value = null;
  }

  return { user, signIn, signOut };
});
```
