<script setup lang="ts">
import { computed } from 'vue'
import StaffLayout from '@/layouts/StaffLayout.vue'
import ParentLayout from '@/layouts/ParentLayout.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'
import PlatformLayout from '@/layouts/PlatformLayout.vue'
import AuthBootstrap from '@/components/auth/AuthBootstrap.vue'
import { useAuth } from '@/composables/useAuth'

const { user } = useAuth()

const layout = computed(() => {
  switch (user.value?.role) {
    case 'parent':
      return ParentLayout
    case 'student':
      return StudentLayout
    case 'super_admin':
      return PlatformLayout
    default:
      return StaffLayout
  }
})
</script>

<template>
  <AuthBootstrap>
    <component :is="layout" />
  </AuthBootstrap>
</template>
