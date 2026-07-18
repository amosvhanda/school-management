<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import SectionAnalyticsHub from '@/components/analytics/SectionAnalyticsHub.vue'
import type { SectionKpi } from '@/components/analytics/SectionAnalyticsHub.vue'
import AcademicsAnalyticsPanel from '@/modules/analytics/components/AcademicsAnalyticsPanel.vue'
import CommunicationsAnalyticsPanel from '@/modules/analytics/components/CommunicationsAnalyticsPanel.vue'
import FinanceAnalyticsPanel from '@/modules/analytics/components/FinanceAnalyticsPanel.vue'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { canAccessNavItem } from '@/lib/permissions'
import { getSectionHub, type SectionKey } from '@/lib/section-hubs'
import {
  loadAcademicsDetails,
  loadCommunicationsDetails,
  loadFinanceDetails,
  loadSectionKpis,
} from '@/modules/analytics/section-analytics.loaders'
import type {
  AcademicReport,
  AcademicsPeriod,
  AttendanceReport,
  ExamAnalyticsReport,
} from '@/modules/analytics/types/academics-analytics'
import type { CommunicationsDetails } from '@/modules/analytics/types/communications-analytics'
import type { FinanceDetails } from '@/modules/analytics/types/finance-analytics'
import type { AttendanceSummary } from '@/types/dashboard'

const route = useRoute()
const { user } = useAuth()
const sectionKey = computed(() => route.meta.sectionKey as SectionKey | undefined)
const hub = computed(() => getSectionHub(sectionKey.value))
const quickLinks = computed(() =>
  (hub.value?.quickLinks ?? []).filter((link) =>
    canAccessNavItem(user.value, link.capability),
  ),
)

const loading = ref(true)
const detailsLoading = ref(false)
const error = ref<string | null>(null)
const kpis = ref<SectionKpi[]>([])
const academicsPeriod = ref<AcademicsPeriod>('30d')

const attendanceReport = ref<AttendanceReport | null>(null)
const academicReport = ref<AcademicReport | null>(null)
const examAnalytics = ref<ExamAnalyticsReport | null>(null)
const todayAttendance = ref<AttendanceSummary | null>(null)

const financeDetails = ref<FinanceDetails | null>(null)
const communicationsDetails = ref<CommunicationsDetails | null>(null)

async function loadKpis() {
  if (!sectionKey.value) return
  kpis.value = await loadSectionKpis(sectionKey.value)
}

async function loadDetails() {
  if (!sectionKey.value) return

  if (sectionKey.value === 'academics') {
    const data = await loadAcademicsDetails(academicsPeriod.value)
    attendanceReport.value = data.attendance
    academicReport.value = data.academic
    examAnalytics.value = data.examAnalytics
    todayAttendance.value = data.todayAttendance
    return
  }

  if (sectionKey.value === 'finance') {
    financeDetails.value = await loadFinanceDetails()
    return
  }

  if (sectionKey.value === 'communications') {
    communicationsDetails.value = await loadCommunicationsDetails()
  }
}

async function load() {
  if (!sectionKey.value || !hub.value) {
    error.value = 'Section not found'
    loading.value = false
    return
  }

  loading.value = true
  error.value = null
  try {
    await Promise.all([loadKpis(), loadDetails()])
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load section analytics')
  } finally {
    loading.value = false
  }
}

async function reloadDetails() {
  if (!sectionKey.value || loading.value) return

  detailsLoading.value = true
  error.value = null
  try {
    await loadDetails()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to refresh analytics')
  } finally {
    detailsLoading.value = false
  }
}

watch(academicsPeriod, () => {
  if (sectionKey.value === 'academics' && !loading.value) {
    void reloadDetails()
  }
})

onMounted(load)
</script>

<template>
  <div v-if="!hub" class="p-8 text-destructive">Unknown analytics section.</div>

  <SectionAnalyticsHub
    v-else
    :title="hub.title"
    :description="hub.description"
    :kpis="kpis"
    :quick-links="quickLinks"
    :loading="loading"
    :error="error"
    @retry="load"
  >
    <AcademicsAnalyticsPanel
      v-if="sectionKey === 'academics'"
      v-model:period="academicsPeriod"
      :loading="detailsLoading"
      :attendance="attendanceReport"
      :academic="academicReport"
      :exam-analytics="examAnalytics"
      :today-summary="todayAttendance"
    />

    <FinanceAnalyticsPanel
      v-else-if="sectionKey === 'finance' && financeDetails"
      :loading="detailsLoading"
      :summary="financeDetails.summary"
      :financial="financeDetails.financial"
      :aging="financeDetails.aging"
      :reconciliation="financeDetails.reconciliation"
      :fee-trend="financeDetails.feeTrend"
      :payroll-pending="
        (financeDetails.payroll?.total_pending ?? 0) + (financeDetails.payroll?.total_partial ?? 0)
      "
    />

    <CommunicationsAnalyticsPanel
      v-else-if="sectionKey === 'communications' && communicationsDetails"
      :threads="communicationsDetails.threads"
      :announcements="communicationsDetails.announcements"
      :unassigned-threads="communicationsDetails.unassignedThreads"
      :open-threads="communicationsDetails.openThreads"
      :active-announcements="communicationsDetails.activeAnnouncements"
    />

  </SectionAnalyticsHub>
</template>
