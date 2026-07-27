import { ref } from 'vue'
import { getErrorMessage } from '@/lib/api-response'
import { getStoredToken } from '@/lib/api'
import { queryClient } from '@/lib/query-client'
import { queryKeys } from '@/lib/query-keys'
import {
  defaultKpis,
  fetchActivity,
  fetchCommandCenter,
  fetchFinanceSummary,
  fetchKpis,
  fetchMonthlyStats,
  fetchPendingWorkflows,
  fetchRecentActivity,
  fetchSchoolWidgets,
} from '@/services/dashboard.service'
import { useAuthStore } from '@/stores/auth.store'
import { useNotificationStore } from '@/stores/notification.store'
import type {
  ActivityPoint,
  CommandCenterData,
  DashboardKpis,
  MonthlyStat,
  RecentActivityItem,
  SchoolDashboardWidgets,
} from '@/types/dashboard'

export interface StaffDashboardLoadOptions {
  analytics?: boolean
  activityFeed?: boolean
  commandCenter?: boolean
  financeSummary?: boolean
  schoolWidgets?: boolean
}

async function fetchWidget<T>(label: string, task: () => Promise<T>): Promise<{ label: string; data: T | null; error?: string }> {
  try {
    return { label, data: await task() }
  } catch (err) {
    return { label, data: null, error: getErrorMessage(err) }
  }
}

export function useStaffDashboard() {
  const authStore = useAuthStore()
  const notificationStore = useNotificationStore()
  const loading = ref(true)
  const error = ref<string | null>(null)
  const partialErrors = ref<string[]>([])
  const lastUpdated = ref<Date | null>(null)
  const kpis = ref<DashboardKpis | null>(null)
  const activity = ref<ActivityPoint[]>([])
  const monthly = ref<MonthlyStat[]>([])
  const recent = ref<RecentActivityItem[]>([])
  const commandCenter = ref<CommandCenterData | null>(null)
  const financeSummary = ref<Record<string, unknown> | null>(null)
  const pendingWorkflows = ref(0)
  const schoolWidgets = ref<SchoolDashboardWidgets | null>(null)

  function userKey() {
    return authStore.user?.id ?? null
  }

  async function load(options: StaffDashboardLoadOptions = {}) {
    loading.value = true
    error.value = null
    partialErrors.value = []

    if (!getStoredToken()) {
      error.value = 'You are not signed in. Please log in to view the dashboard.'
      loading.value = false
      return
    }

    const uid = userKey()
    notificationStore.bindSession(uid)

    const kpiResult = await fetchWidget('KPIs', () =>
      queryClient.fetchQuery({
        queryKey: queryKeys.dashboard.kpis(uid),
        queryFn: fetchKpis,
      }),
    )

    kpis.value = kpiResult.data ?? defaultKpis()
    if (kpiResult.error) {
      partialErrors.value.push(`${kpiResult.label}: ${kpiResult.error}`)
      error.value = kpiResult.error
    }

    const tasks: Promise<void>[] = []

    if (options.analytics) {
      tasks.push(
        fetchWidget('Activity chart', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.activity(uid),
            queryFn: () => fetchActivity(),
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          activity.value = result.data ?? []
        }),
        fetchWidget('Monthly stats', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.monthly(uid),
            queryFn: fetchMonthlyStats,
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          monthly.value = result.data ?? []
        }),
      )
    }

    if (options.activityFeed) {
      tasks.push(
        fetchWidget('Recent activity', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.recent(uid),
            queryFn: () => fetchRecentActivity(),
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          recent.value = result.data ?? []
          notificationStore.setRecentActivity(recent.value)
        }),
        fetchWidget('Workflows', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.workflows(uid),
            queryFn: fetchPendingWorkflows,
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          pendingWorkflows.value = result.data?.length ?? 0
          if (result.data) notificationStore.setWorkflowCount(result.data.length)
        }),
      )
    }

    if (options.commandCenter) {
      tasks.push(
        fetchWidget('Command center', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.commandCenter(uid),
            queryFn: fetchCommandCenter,
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          commandCenter.value = result.data
        }),
      )
    }

    if (options.financeSummary) {
      tasks.push(
        fetchWidget('Finance summary', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.financeSummary(uid),
            queryFn: fetchFinanceSummary,
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          financeSummary.value = result.data
        }),
      )
    }

    if (options.schoolWidgets) {
      tasks.push(
        fetchWidget('School widgets', () =>
          queryClient.fetchQuery({
            queryKey: queryKeys.dashboard.schoolWidgets(uid),
            queryFn: fetchSchoolWidgets,
          }),
        ).then((result) => {
          if (result.error) partialErrors.value.push(`${result.label}: ${result.error}`)
          schoolWidgets.value = result.data
        }),
      )
    }

    await Promise.all(tasks)

    if (kpiResult.data) error.value = null
    lastUpdated.value = new Date()
    loading.value = false
  }

  return {
    loading,
    error,
    partialErrors,
    lastUpdated,
    kpis,
    activity,
    monthly,
    recent,
    commandCenter,
    financeSummary,
    pendingWorkflows,
    schoolWidgets,
    load,
  }
}
