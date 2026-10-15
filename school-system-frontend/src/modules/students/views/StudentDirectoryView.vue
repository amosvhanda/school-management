<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  Banknote,
  GraduationCap,
  UserMinus,
  UserRound,
} from '@lucide/vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { formatMoney } from '@/lib/finance-constants'
import RegistrySection from '@/modules/shared/RegistrySection.vue'
import { fetchKpis } from '@/services/dashboard.service'
import type { DashboardKpis } from '@/types/dashboard'

const { school } = useSchoolProfile()
const kpis = ref<DashboardKpis | null>(null)

const attendance = computed(() => kpis.value?.attendanceSummary)
const present = computed(() => attendance.value?.present ?? 0)
const absent = computed(() => attendance.value?.absent ?? 0)
const marked = computed(() => attendance.value?.total ?? 0)
const presentRate = computed(() => {
  if (marked.value <= 0) return 'No marks today'
  return `${Math.round((present.value / marked.value) * 1000) / 10}% of ${marked.value.toLocaleString()} marked`
})

async function load() {
  try {
    kpis.value = await fetchKpis()
  } catch {
    kpis.value = null
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <PageShell
    title="Students"
    eyebrow="Dashboard / Peoples / Students"
    description="Learner records, attendance today, and outstanding balances."
    max-width="wide"
  >
    <section
      aria-labelledby="student-directory-kpis"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <h2 id="student-directory-kpis" class="sr-only">Student summary</h2>
      <KpiCard
        title="Total Students"
        :value="(kpis?.totalStudents ?? 0).toLocaleString()"
        :subtitle="`${kpis?.activeStudents ?? 0} active · ${kpis?.totalClasses ?? 0} classes`"
        :icon="GraduationCap"
      />
      <KpiCard
        title="Present Today"
        :value="present.toLocaleString()"
        :subtitle="presentRate"
        :icon="UserRound"
        accent="success"
      />
      <KpiCard
        title="Absent Today"
        :value="absent.toLocaleString()"
        :subtitle="`${attendance?.excused ?? 0} excused · ${attendance?.late ?? 0} late`"
        :icon="UserMinus"
        accent="danger"
      />
      <KpiCard
        title="Fee Pending"
        :value="formatMoney(kpis?.outstandingFees ?? 0, school?.currency)"
        subtitle="Outstanding student balances"
        :icon="Banknote"
        accent="warning"
      />
    </section>

    <RegistrySection list-key="students" />
  </PageShell>
</template>
