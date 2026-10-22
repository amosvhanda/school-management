<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import ModuleHub from '@/components/layout/ModuleHub.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { TEACHING_HUB_DEFAULT_TAB, TEACHING_HUB_TABS } from '@/modules/teaching/teaching-hub-tabs'

const { checkCapability, user } = useAuth()

const isSchoolManager = computed(() => checkCapability('canManageTeachers'))
const lacksTeacherProfile = computed(() => !user.value?.teacher_id)
/** Unlinked teachers cannot use LMS APIs; school managers can manage school-wide LMS. */
const blockedUnlinkedTeacher = computed(
  () => lacksTeacherProfile.value && !isSchoolManager.value,
)

const tabs = computed(() => {
  if (!lacksTeacherProfile.value) return TEACHING_HUB_TABS
  if (!isSchoolManager.value) return []
  return TEACHING_HUB_TABS.filter((tab) => !tab.requiresTeacherProfile)
})

const title = computed(() => (isSchoolManager.value ? 'LMS / Teaching' : 'Teaching'))
const description = computed(() =>
  lacksTeacherProfile.value && isSchoolManager.value
    ? 'School LMS management — create and review online lessons for teachers. Open Dashboard → LMS for analytics.'
    : 'Your classroom workspace — pick a class, then teach. Attendance and gradebook stay in Classroom.',
)
const defaultTab = computed(() =>
  lacksTeacherProfile.value && isSchoolManager.value ? 'lms' : TEACHING_HUB_DEFAULT_TAB,
)
</script>

<template>
  <PageShell
    v-if="blockedUnlinkedTeacher"
    title="Teaching"
    description="Your classroom workspace needs a linked teacher profile."
  >
    <EmptyState
      title="Teacher profile not linked"
      description="This account cannot open Teaching or LMS until a teacher profile is linked. Ask an administrator to link your user under People → Teachers."
    >
      <Button variant="outline" as-child>
        <RouterLink to="/">Back to dashboard</RouterLink>
      </Button>
    </EmptyState>
  </PageShell>

  <ModuleHub
    v-else
    :title="title"
    :description="description"
    route-name="teaching"
    nav-layout="sidebar"
    :tabs="tabs"
    :default-tab="defaultTab"
    aria-label="Teaching sections"
  />
</template>
