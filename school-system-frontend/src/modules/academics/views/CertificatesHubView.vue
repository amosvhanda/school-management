<script setup lang="ts">
import { Award, FileStack } from '@lucide/vue'
import ModuleHub from '@/components/layout/ModuleHub.vue'
import type { ModuleHubTab } from '@/lib/module-hub'

const CERTIFICATES_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'templates',
    title: 'Templates',
    description: 'Reusable certificate layouts with merge placeholders.',
    icon: FileStack,
    capability: 'canManageTeachers',
    listKey: 'academics-certificate-templates',
  },
  {
    id: 'issued',
    title: 'Issued',
    description: 'Certificates already issued to students.',
    icon: Award,
    capability: 'canManageTeachers',
    listKey: 'academics-school-certificates',
  },
]
</script>

<template>
  <ModuleHub
    title="Certificates"
    description="Design templates and review certificates issued to students."
    route-name="academics-certificates"
    :tabs="CERTIFICATES_HUB_TABS"
    default-tab="templates"
    aria-label="Certificate sections"
  />
</template>
