<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

const showBanner = computed(() => {
  const license = user.value?.license
  return license && license.status !== 'active'
})
</script>

<template>
  <Alert v-if="showBanner" variant="destructive" class="rounded-none border-x-0 border-t-0">
    <AlertTitle>License attention required</AlertTitle>
    <AlertDescription class="flex items-center justify-between gap-4">
      <span>{{ user?.license?.message ?? 'Your school license needs attention.' }}</span>
      <Button as-child size="sm" variant="outline">
        <RouterLink to="/license/activate">Activate license</RouterLink>
      </Button>
    </AlertDescription>
  </Alert>
</template>
