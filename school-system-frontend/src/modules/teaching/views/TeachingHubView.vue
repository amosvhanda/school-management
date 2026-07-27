<script setup lang="ts">
import { computed } from 'vue'
import ModuleHub from '@/components/layout/ModuleHub.vue'
import { useAuth } from '@/composables/useAuth'
import { TEACHING_HUB_DEFAULT_TAB, TEACHING_HUB_TABS } from '@/modules/teaching/teaching-hub-tabs'

/** Tabs that call teacher-portal APIs requiring a linked teacher profile. */
const TEACHER_PROFILE_TAB_IDS = new Set([
  'classes',
  'lessons',
  'homework',
  'behaviour',
  'resources',
  'syllabus',
  'report-cards',
  'assessments',
  'calendar',
  'timetable-tools',
  'leave',
  'cover',
  'department',
  'exports',
  'notifications',
  'ai',
])

const { checkCapability, user } = useAuth()

/** Anyone without a teacher profile only gets school-wide LMS (admins) — never teacher-only APIs. */
const lacksTeacherProfile = computed(() => !user.value?.teacher_id)

const tabs = computed(() => {
  if (!lacksTeacherProfile.value) return TEACHING_HUB_TABS
  return TEACHING_HUB_TABS.filter((tab) => !TEACHER_PROFILE_TAB_IDS.has(tab.id))
})

const title = computed(() => (checkCapability('canManageTeachers') ? 'LMS / Teaching' : 'Teaching'))
const description = computed(() =>
  lacksTeacherProfile.value
    ? 'School LMS management — create and review online lessons for teachers. Open Dashboard → LMS for analytics.'
    : 'Your classroom workspace — pick a class, then teach. Attendance and gradebook stay in Classroom.',
)
const defaultTab = computed(() =>
  lacksTeacherProfile.value ? 'lms' : TEACHING_HUB_DEFAULT_TAB,
)
</script>

<template>
  <ModuleHub
    :title="title"
    :description="description"
    route-name="teaching"
    nav-layout="sidebar"
    :tabs="tabs"
    :default-tab="defaultTab"
    aria-label="Teaching sections"
  />
</template>
