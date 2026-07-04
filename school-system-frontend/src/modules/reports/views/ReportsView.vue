<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Download, FileBarChart } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { enrichRelationLabelsDeep } from '@/lib/relation-display'
import { fetchKpis } from '@/services/dashboard.service'
import { reportsApi } from '@/services/api.service'
import type { DashboardKpis } from '@/types/dashboard'

type ReportKey = 'academic' | 'attendance' | 'financial'
type ExportFormat = 'json' | 'csv'

const reportTypes: Record<ReportKey, 'academic-performance' | 'attendance' | 'financial'> = {
  academic: 'academic-performance',
  attendance: 'attendance',
  financial: 'financial',
}

const toast = useToast()
const loadingKey = ref<string | null>(null)
const kpis = ref<DashboardKpis | null>(null)
const metricsLoading = ref(true)

const overviewCards = computed<MetricCard[]>(() => {
  if (!kpis.value) return []
  return [
    {
      title: 'Active students',
      value: kpis.value.activeStudents,
      subtitle: `${kpis.value.totalStudents} enrolled`,
      href: '/students',
    },
    {
      title: 'Attendance today',
      value: `${kpis.value.attendanceSummary.present}/${kpis.value.attendanceSummary.total}`,
      subtitle: `${kpis.value.attendanceSummary.absent} absent · ${kpis.value.attendanceSummary.late} late`,
      href: '/academics/attendance',
    },
    {
      title: 'Outstanding fees',
      value: `$${kpis.value.outstandingFees.toLocaleString()}`,
      subtitle: 'Unpaid balances',
      accent: 'danger' as const,
      href: '/finance/invoices',
    },
    {
      title: 'Revenue today',
      value: `$${kpis.value.paymentsToday.toLocaleString()}`,
      subtitle: 'Collections recorded today',
      accent: 'success' as const,
      href: '/finance/payments',
    },
  ]
})

const reports = [
  { key: 'academic', title: 'Academic performance', description: 'Grades and class averages' },
  { key: 'attendance', title: 'Attendance summary', description: 'Presence and absence trends' },
  { key: 'financial', title: 'Financial summary', description: 'Fees, payments, and balances' },
]

function csvValue(value: unknown): string {
  if (value == null) return ''
  if (typeof value === 'string') return value
  if (typeof value === 'number' || typeof value === 'boolean') return String(value)
  return JSON.stringify(value)
}

function csvEscape(value: unknown): string {
  const raw = csvValue(value)
  const escaped = raw.replace(/"/g, '""')
  return /[",\n]/.test(escaped) ? `"${escaped}"` : escaped
}

function toCsv(rows: Array<Record<string, unknown>>): string {
  if (!rows.length) return ''
  const headers = Array.from(
    rows.reduce((set, row) => {
      Object.keys(row).forEach((key) => set.add(key))
      return set
    }, new Set<string>()),
  )

  const lines = [headers.map((h) => csvEscape(h)).join(',')]
  for (const row of rows) {
    lines.push(headers.map((header) => csvEscape(row[header])).join(','))
  }
  return lines.join('\n')
}

function downloadBlob(content: BlobPart, contentType: string, filename: string) {
  const blob = new Blob([content], { type: contentType })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

async function runReport(key: ReportKey, format: ExportFormat) {
  loadingKey.value = `${key}-${format}`
  try {
    const data = await reportsApi.export({
      type: reportTypes[key],
      format: 'json',
    })
    const formatted = enrichRelationLabelsDeep(data)

    if (format === 'csv') {
      const payload = (formatted && typeof formatted === 'object')
        ? (formatted as Record<string, unknown>)
        : {}
      const records = Array.isArray(payload.records)
        ? (payload.records as Array<Record<string, unknown>>)
        : []
      const csv = toCsv(records)
      downloadBlob(csv, 'text/csv;charset=utf-8', `${key}-report.csv`)
    } else {
      downloadBlob(JSON.stringify(formatted, null, 2), 'application/json', `${key}-report.json`)
    }

    toast.success('Report downloaded')
  } catch (err) {
    toast.error('Report failed', getErrorMessage(err))
  } finally {
    loadingKey.value = null
  }
}

onMounted(async () => {
  try {
    kpis.value = await fetchKpis()
  } catch {
    kpis.value = null
  } finally {
    metricsLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-8">
    <MetricBand
      title="Reports"
      description="Review the live school picture first, then export the report pack you need in JSON or CSV."
      :cards="overviewCards"
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card v-for="report in reports" :key="report.key">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <FileBarChart class="h-5 w-5 text-muted-foreground" aria-hidden="true" />
            {{ report.title }}
          </CardTitle>
          <CardDescription>{{ report.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Button
              variant="outline"
              :disabled="loadingKey === `${report.key}-json` || loadingKey === `${report.key}-csv`"
              @click="runReport(report.key as ReportKey, 'json')"
            >
              <Download class="mr-2 h-4 w-4" aria-hidden="true" />
              {{ loadingKey === `${report.key}-json` ? 'Generating…' : 'Download JSON' }}
            </Button>
            <Button
              variant="secondary"
              :disabled="loadingKey === `${report.key}-json` || loadingKey === `${report.key}-csv`"
              @click="runReport(report.key as ReportKey, 'csv')"
            >
              <Download class="mr-2 h-4 w-4" aria-hidden="true" />
              {{ loadingKey === `${report.key}-csv` ? 'Generating…' : 'Download CSV' }}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <PageLoader v-if="loadingKey" :label="`Generating ${loadingKey} report`" />
  </div>
</template>
