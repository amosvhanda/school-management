<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  Briefcase,
  CalendarClock,
  GraduationCap,
  Wallet,
} from '@lucide/vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { formatMoney } from '@/lib/finance-constants'
import RegistrySection from '@/modules/shared/RegistrySection.vue'
import { fetchKpis } from '@/services/dashboard.service'
import type { DashboardKpis } from '@/types/dashboard'

const route = useRoute()
const { school } = useSchoolProfile()
const kpis = ref<DashboardKpis | null>(null)
const autoCreate = computed(() => route.query.create === '1')

const payroll = computed(() => kpis.value?.payrollSummary)
const pendingLeave = computed(() => kpis.value?.pendingLeaveRequests ?? 0)

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
    title="Teachers"
    eyebrow="Dashboard / Peoples / Teachers"
    description="Teaching staff records, leave queue, and payroll status."
    max-width="wide"
  >
    <section
      aria-labelledby="teacher-directory-kpis"
      class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
    >
      <h2 id="teacher-directory-kpis" class="sr-only">Teacher summary</h2>
      <KpiCard
        title="Total Teachers"
        :value="(kpis?.totalTeachers ?? 0).toLocaleString()"
        :subtitle="`${kpis?.totalStaff ?? 0} other staff · ${kpis?.totalClasses ?? 0} classes`"
        :icon="GraduationCap"
      />
      <KpiCard
        title="Classes"
        :value="(kpis?.totalClasses ?? 0).toLocaleString()"
        subtitle="Classes across the school"
        :icon="Briefcase"
        accent="success"
      />
      <KpiCard
        title="Pending Leave"
        :value="pendingLeave.toLocaleString()"
        subtitle="Open leave requests"
        :icon="CalendarClock"
        accent="warning"
      />
      <KpiCard
        title="Payroll Pending"
        :value="formatMoney(payroll?.total_pending ?? 0, school?.currency)"
        :subtitle="`${formatMoney(payroll?.total_paid ?? 0, school?.currency)} paid`"
        :icon="Wallet"
        accent="danger"
      />
    </section>

    <RegistrySection list-key="teachers" :auto-create="autoCreate" />
  </PageShell>
</template>
