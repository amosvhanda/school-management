<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { z } from 'zod'
import {
  CalendarDays,
  Download,
  Eye,
  FileBarChart,
  Filter,
  Plus,
  Search,
  TrendingUp,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { toast } from 'vue-sonner'
import { getErrorMessage } from '@/lib/api-response'
import { enrichRelationLabelsDeep } from '@/lib/relation-display'
import { formatDate, formatDateTime } from '@/lib/format'
import { cn } from '@/lib/utils'
import { formatMoney } from '@/lib/finance-constants'
import { financeApi, reportsApi } from '@/services/api.service'

type ReportExportType = 'academic-performance' | 'attendance' | 'financial'
type TemplateStatus = 'active' | 'scheduled' | 'completed' | 'due'

interface ReportTemplate {
  id: number
  name: string
  description?: string | null
  category?: string | null
  frequency?: string | null
  recipients?: string[] | null
  parameters?: Record<string, unknown> | null
  created_at?: string
  updated_at?: string
}

interface RecentReport {
  id: string
  name: string
  category: string
  generated_at: string
  total_records: number
  type: string
}

interface PreviewState {
  title: string
  category: string
  generated_at?: string
  total_records?: number
  summary: Array<{ label: string; value: string }>
}

const RECENT_KEY = 'school-reports-recent-v1'

const loading = ref(true)
const error = ref<string | null>(null)
const templates = ref<ReportTemplate[]>([])
const recent = ref<RecentReport[]>([])
const activeTab = ref('templates')
const searchQuery = ref('')
const categoryFilter = ref('all')
const showAdvanced = ref(false)
const frequencyFilter = ref('all')
const generatingId = ref<number | string | null>(null)
const preview = ref<PreviewState | null>(null)
const createOpen = ref(false)
const createSaving = ref(false)
const analyticsLoading = ref(false)
const academicSummary = ref<Record<string, unknown> | null>(null)
const attendanceSummary = ref<Record<string, unknown> | null>(null)
const financialSummary = ref<Record<string, unknown> | null>(null)

const createFields: FormFieldSchema[] = [
  { name: 'name', label: 'Report name', type: 'text', required: true },
  {
    name: 'category',
    label: 'Category',
    type: 'select',
    required: true,
    options: [
      { label: 'Academic', value: 'academic' },
      { label: 'Attendance', value: 'attendance' },
      { label: 'Finance', value: 'financial' },
      { label: 'HR / Payroll', value: 'payroll' },
      { label: 'Analytics', value: 'analytics' },
    ],
  },
  {
    name: 'frequency',
    label: 'Frequency',
    type: 'select',
    options: [
      { label: 'Weekly', value: 'weekly' },
      { label: 'Monthly', value: 'monthly' },
      { label: 'Termly', value: 'termly' },
      { label: 'On demand', value: 'on_demand' },
    ],
  },
  {
    name: 'description',
    label: 'Description',
    type: 'textarea',
    colSpan: 2,
  },
]

const createSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  category: z.string().min(1),
  frequency: z.string().optional(),
  description: z.string().optional(),
})

const categories = computed(() => {
  const set = new Set<string>()
  for (const t of templates.value) {
    if (t.category) set.add(t.category)
  }
  return [...set].sort()
})

const filteredTemplates = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return templates.value.filter((t) => {
    if (categoryFilter.value !== 'all' && t.category !== categoryFilter.value) return false
    if (frequencyFilter.value !== 'all' && (t.frequency ?? '') !== frequencyFilter.value) return false
    if (!q) return true
    const hay = [t.name, t.description, t.category, t.frequency].filter(Boolean).join(' ').toLowerCase()
    return hay.includes(q)
  })
})

const scheduledTemplates = computed(() =>
  templates.value.filter((t) => {
    const f = String(t.frequency ?? '').toLowerCase()
    return f && f !== 'on_demand' && f !== 'ondemand'
  }),
)

const kpis = computed(() => {
  const monthStart = new Date()
  monthStart.setDate(1)
  monthStart.setHours(0, 0, 0, 0)
  const thisMonth = recent.value.filter((r) => new Date(r.generated_at) >= monthStart).length
  const due = templates.value.filter((t) => templateStatus(t) === 'due').length
  return {
    active: templates.value.length,
    thisMonth,
    downloads: recent.value.length,
    pending: due,
  }
})

function categoryLabel(category?: string | null) {
  const c = String(category ?? '').toLowerCase()
  if (c === 'academic') return 'Academic'
  if (c === 'attendance') return 'Attendance'
  if (c === 'financial' || c === 'finance') return 'Finance'
  if (c === 'payroll' || c === 'hr') return 'HR'
  if (c === 'analytics') return 'Analytics'
  return category ? String(category) : 'General'
}

function resolveExportType(template: ReportTemplate): ReportExportType | 'payroll' | null {
  const c = String(template.category ?? '').toLowerCase()
  const name = String(template.name ?? '').toLowerCase()
  if (c === 'academic' || name.includes('performance') || name.includes('academic')) {
    return 'academic-performance'
  }
  if (c === 'attendance' || name.includes('attendance')) return 'attendance'
  if (c === 'financial' || c === 'finance' || name.includes('financial')) return 'financial'
  if (c === 'payroll' || name.includes('payroll')) return 'payroll'
  return null
}

function templateStatus(template: ReportTemplate): TemplateStatus {
  const last = lastGeneratedAt(template)
  const freq = String(template.frequency ?? '').toLowerCase()
  if (!last) return 'due'
  const days = (Date.now() - new Date(last).getTime()) / (1000 * 60 * 60 * 24)
  if (freq.includes('week') && days > 7) return 'due'
  if (freq.includes('month') && days > 31) return 'due'
  if (freq.includes('term') && days > 100) return 'due'
  if (freq.includes('week') || freq.includes('month') || freq.includes('term')) return 'scheduled'
  if (days <= 7) return 'completed'
  return 'active'
}

function statusBadgeClass(status: TemplateStatus) {
  switch (status) {
    case 'scheduled':
      return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300'
    case 'active':
      return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300'
    case 'due':
      return 'border-destructive/30 bg-destructive/10 text-destructive'
    default:
      return 'border-border bg-muted text-muted-foreground'
  }
}

function lastGeneratedAt(template: ReportTemplate) {
  const match = recent.value.find(
    (r) => r.name === template.name || r.category === categoryLabel(template.category),
  )
  return match?.generated_at ?? template.updated_at ?? null
}

function recipientCount(template: ReportTemplate) {
  return Array.isArray(template.recipients) ? template.recipients.length : 0
}

function loadRecent() {
  try {
    const raw = localStorage.getItem(RECENT_KEY)
    recent.value = raw ? (JSON.parse(raw) as RecentReport[]) : []
  } catch {
    recent.value = []
  }
}

function pushRecent(entry: RecentReport) {
  recent.value = [entry, ...recent.value.filter((r) => r.id !== entry.id)].slice(0, 30)
  localStorage.setItem(RECENT_KEY, JSON.stringify(recent.value))
}

function buildPreview(
  title: string,
  category: string,
  data: Record<string, unknown>,
): PreviewState {
  const summary: Array<{ label: string; value: string }> = []
  const total = Number(data.total_records ?? 0)
  if (data.by_status && typeof data.by_status === 'object') {
    for (const [k, v] of Object.entries(data.by_status as Record<string, number>)) {
      summary.push({ label: k, value: String(v) })
    }
  }
  if (Array.isArray(data.by_subject)) {
    summary.push({ label: 'Subjects', value: String((data.by_subject as unknown[]).length) })
  }
  if (Array.isArray(data.by_class)) {
    summary.push({ label: 'Classes', value: String((data.by_class as unknown[]).length) })
  }
  if (data.outstanding != null) {
    summary.push({ label: 'Outstanding', value: formatMoney(data.outstanding) })
  }
  if (data.collected != null) {
    summary.push({ label: 'Collected', value: formatMoney(data.collected) })
  }
  if (data.total_payroll != null) {
    summary.push({ label: 'Total payroll', value: formatMoney(data.total_payroll) })
  }
  if (data.paid_employees != null) {
    summary.push({ label: 'Employees paid', value: String(data.paid_employees) })
  }
  if (!summary.length) {
    summary.push({ label: 'Records', value: String(total) })
  }
  return {
    title,
    category,
    generated_at: String(data.generated_at ?? new Date().toISOString()),
    total_records: total,
    summary: summary.slice(0, 6),
  }
}

async function loadTemplates() {
  loading.value = true
  error.value = null
  try {
    templates.value = (await reportsApi.templates()) as ReportTemplate[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load report templates')
  } finally {
    loading.value = false
  }
}

async function generateTemplate(template: ReportTemplate, download = false) {
  const type = resolveExportType(template)
  if (!type) {
    toast.warning('Unsupported template', {
      description: 'This category does not have a generator yet.',
    })
    return
  }

  generatingId.value = template.id
  try {
    let data: Record<string, unknown>
    if (type === 'payroll') {
      data = (await financeApi.payroll.summary()) as Record<string, unknown>
      data = {
        ...data,
        report_type: 'payroll',
        generated_at: new Date().toISOString(),
        total_records: Number(data.total_employees ?? 0),
      }
    } else {
      data = enrichRelationLabelsDeep(
        await reportsApi.export({ type, format: 'json' }),
      ) as Record<string, unknown>
    }

    preview.value = buildPreview(template.name, categoryLabel(template.category), data)
    pushRecent({
      id: `${template.id}-${Date.now()}`,
      name: template.name,
      category: categoryLabel(template.category),
      generated_at: String(data.generated_at ?? new Date().toISOString()),
      total_records: Number(data.total_records ?? 0),
      type: String(type),
    })

    if (download) {
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${template.name.toLowerCase().replace(/\s+/g, '-')}.json`
      a.click()
      URL.revokeObjectURL(url)
    }

    toast.success('Report generated', { description: template.name })
    activeTab.value = 'recent'
  } catch (err) {
    toast.error('Generate failed', { description: getErrorMessage(err) })
  } finally {
    generatingId.value = null
  }
}

async function onCreateSubmit(values: Record<string, unknown>) {
  createSaving.value = true
  try {
    await reportsApi.store({
      name: values.name,
      category: values.category,
      frequency: values.frequency || 'on_demand',
      description: values.description || null,
      recipients: ['admin'],
      parameters: {},
    })
    toast.success('Report template created')
    createOpen.value = false
    await loadTemplates()
  } catch (err) {
    toast.error('Could not create template', { description: getErrorMessage(err) })
  } finally {
    createSaving.value = false
  }
}

async function loadAnalytics() {
  analyticsLoading.value = true
  try {
    const [academic, attendance, financial] = await Promise.all([
      reportsApi.academicPerformance().catch(() => null),
      reportsApi.attendance().catch(() => null),
      reportsApi.financial().catch(() => null),
    ])
    academicSummary.value = academic as Record<string, unknown> | null
    attendanceSummary.value = attendance as Record<string, unknown> | null
    financialSummary.value = financial as Record<string, unknown> | null
  } finally {
    analyticsLoading.value = false
  }
}

watch(activeTab, (tab) => {
  if (tab === 'analytics') void loadAnalytics()
})

onMounted(async () => {
  loadRecent()
  await loadTemplates()
})
</script>

<template>
  <PageShell
    title="Reports & Analytics"
    description="Generate, schedule, and manage school reports."
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" @click="showAdvanced = !showAdvanced">
        <Filter class="mr-2 size-4" aria-hidden="true" />
        Advanced Filter
      </Button>
      <Button @click="createOpen = true">
        <Plus class="mr-2 size-4" aria-hidden="true" />
        Create Report
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading reports workspace…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadTemplates" />

    <div v-else class="space-y-6">
      <section aria-labelledby="reports-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="reports-kpis" class="sr-only">Reports summary</h2>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Active Reports</p>
              <p class="text-3xl font-semibold tabular-nums">{{ kpis.active }}</p>
              <p class="text-xs text-muted-foreground">Report templates</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <FileBarChart class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">This Month</p>
              <p class="text-3xl font-semibold tabular-nums">{{ kpis.thisMonth }}</p>
              <p class="text-xs text-muted-foreground">Reports generated</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <CalendarDays class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Total Downloads</p>
              <p class="text-3xl font-semibold tabular-nums">{{ kpis.downloads }}</p>
              <p class="text-xs text-emerald-600">Session history</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
              <Download class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Pending Reports</p>
              <p class="text-3xl font-semibold tabular-nums text-destructive">{{ kpis.pending }}</p>
              <p class="text-xs text-muted-foreground">Due for generation</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
              <TrendingUp class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>
      </section>

      <div
        v-if="showAdvanced"
        class="flex flex-wrap items-end gap-3 rounded-xl border border-border/60 bg-muted/30 p-4"
      >
        <div class="space-y-1">
          <p class="text-xs font-medium text-muted-foreground">Frequency</p>
          <Select v-model="frequencyFilter">
            <SelectTrigger class="h-10 w-44">
              <SelectValue placeholder="All frequencies" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All frequencies</SelectItem>
              <SelectItem value="weekly">Weekly</SelectItem>
              <SelectItem value="monthly">Monthly</SelectItem>
              <SelectItem value="termly">Termly</SelectItem>
              <SelectItem value="on_demand">On demand</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Reports views">
          <TabsTrigger value="templates">Report Templates</TabsTrigger>
          <TabsTrigger value="recent">Recent Reports</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
          <TabsTrigger value="scheduled">Scheduled</TabsTrigger>
        </TabsList>

        <TabsContent value="templates">
          <Card class="border-border/70 shadow-sm">
            <CardHeader class="border-b border-border/60 pb-4">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                  <CardTitle class="text-base">Report Templates</CardTitle>
                  <CardDescription>
                    Manage and generate reports from predefined templates.
                  </CardDescription>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                  <div class="relative w-full sm:w-72">
                    <Search
                      class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                      aria-hidden="true"
                    />
                    <Input
                      v-model="searchQuery"
                      type="search"
                      class="h-10 pl-9"
                      placeholder="Search report templates…"
                      aria-label="Search report templates"
                    />
                  </div>
                  <Select v-model="categoryFilter">
                    <SelectTrigger class="h-10 w-full sm:w-48" aria-label="Filter by category">
                      <SelectValue placeholder="All Categories" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Categories</SelectItem>
                      <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                        {{ categoryLabel(cat) }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>

            <CardContent class="px-6 py-6">
              <p
                v-if="!filteredTemplates.length"
                class="py-10 text-center text-sm text-muted-foreground"
              >
                {{ templates.length ? 'No templates match your filters.' : 'No report templates yet. Create one to get started.' }}
              </p>

              <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                  v-for="template in filteredTemplates"
                  :key="template.id"
                  class="flex flex-col rounded-xl border border-border/70 bg-card p-5 shadow-sm"
                >
                  <div class="mb-3 flex flex-wrap items-center gap-2">
                    <Badge variant="outline" class="capitalize">
                      {{ categoryLabel(template.category) }}
                    </Badge>
                    <Badge
                      variant="outline"
                      :class="cn('capitalize', statusBadgeClass(templateStatus(template)))"
                    >
                      {{ templateStatus(template) }}
                    </Badge>
                  </div>

                  <h3 class="text-base font-semibold text-foreground">{{ template.name }}</h3>
                  <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                    {{ template.description || 'School report template' }}
                  </p>

                  <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                      <dt class="text-muted-foreground">Frequency</dt>
                      <dd class="capitalize font-medium">{{ template.frequency || 'On demand' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                      <dt class="text-muted-foreground">Last Generated</dt>
                      <dd class="font-medium">
                        {{ lastGeneratedAt(template) ? formatDate(lastGeneratedAt(template)) : 'Never' }}
                      </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                      <dt class="text-muted-foreground">Recipients</dt>
                      <dd class="font-medium">{{ recipientCount(template) }}</dd>
                    </div>
                  </dl>

                  <div class="mt-auto flex items-center gap-2 pt-5">
                    <Button
                      class="flex-1"
                      :disabled="generatingId === template.id"
                      :aria-busy="generatingId === template.id"
                      @click="generateTemplate(template, true)"
                    >
                      {{ generatingId === template.id ? 'Generating…' : 'Generate Now' }}
                    </Button>
                    <Button
                      variant="outline"
                      size="icon"
                      class="size-10 shrink-0"
                      :aria-label="`Preview ${template.name}`"
                      :disabled="generatingId === template.id"
                      @click="generateTemplate(template, false)"
                    >
                      <Eye class="size-4" aria-hidden="true" />
                    </Button>
                  </div>
                </article>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="recent" class="space-y-4">
          <Card v-if="preview" class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">{{ preview.title }}</CardTitle>
              <CardDescription>
                {{ preview.category }}
                <span v-if="preview.generated_at"> · {{ formatDateTime(preview.generated_at) }}</span>
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                  v-for="item in preview.summary"
                  :key="item.label"
                  class="rounded-xl border border-border/60 px-4 py-3"
                >
                  <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    {{ item.label }}
                  </p>
                  <p class="mt-1 text-lg font-semibold tabular-nums">{{ item.value }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Recent reports</CardTitle>
              <CardDescription>Reports generated in this browser session.</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <EmptyState
                v-if="!recent.length"
                variant="embedded"
                title="No recent reports"
                description="Generate a template to see it listed here."
              />
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Report</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Generated</TableHead>
                      <TableHead>Records</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="row in recent" :key="row.id">
                      <TableCell class="font-medium">{{ row.name }}</TableCell>
                      <TableCell>{{ row.category }}</TableCell>
                      <TableCell>{{ formatDateTime(row.generated_at) }}</TableCell>
                      <TableCell class="tabular-nums">{{ row.total_records }}</TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics">
          <PageLoader v-if="analyticsLoading" class="py-12" label="Loading analytics…" />
          <div v-else class="grid gap-4 lg:grid-cols-3">
            <Card class="border-border/70 shadow-sm">
              <CardHeader>
                <CardTitle class="text-base">Academic</CardTitle>
                <CardDescription>Performance snapshot</CardDescription>
              </CardHeader>
              <CardContent class="space-y-2 text-sm">
                <p>Records: <strong>{{ academicSummary?.total_records ?? 0 }}</strong></p>
                <p>
                  Subjects:
                  <strong>{{ Array.isArray(academicSummary?.by_subject) ? academicSummary.by_subject.length : 0 }}</strong>
                </p>
                <p>
                  Classes:
                  <strong>{{ Array.isArray(academicSummary?.by_class) ? academicSummary.by_class.length : 0 }}</strong>
                </p>
              </CardContent>
            </Card>

            <Card class="border-border/70 shadow-sm">
              <CardHeader>
                <CardTitle class="text-base">Attendance</CardTitle>
                <CardDescription>Status breakdown</CardDescription>
              </CardHeader>
              <CardContent class="space-y-2 text-sm">
                <p>Records: <strong>{{ attendanceSummary?.total_records ?? 0 }}</strong></p>
                <template v-if="attendanceSummary?.by_status && typeof attendanceSummary.by_status === 'object'">
                  <p
                    v-for="(count, status) in (attendanceSummary.by_status as Record<string, number>)"
                    :key="status"
                    class="capitalize"
                  >
                    {{ status }}: <strong>{{ count }}</strong>
                  </p>
                </template>
              </CardContent>
            </Card>

            <Card class="border-border/70 shadow-sm">
              <CardHeader>
                <CardTitle class="text-base">Finance</CardTitle>
                <CardDescription>Collections overview</CardDescription>
              </CardHeader>
              <CardContent class="space-y-2 text-sm">
                <p>
                  Outstanding:
                  <strong>{{ formatMoney(financialSummary?.outstanding ?? 0) }}</strong>
                </p>
                <p>
                  Collected:
                  <strong>{{ formatMoney(financialSummary?.collected ?? 0) }}</strong>
                </p>
                <p>Invoices: <strong>{{ financialSummary?.total_invoices ?? 0 }}</strong></p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="scheduled">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Scheduled templates</CardTitle>
              <CardDescription>
                Frequency metadata on templates. Automated email delivery is not enabled yet — generate manually from Templates.
              </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <EmptyState
                v-if="!scheduledTemplates.length"
                variant="embedded"
                title="No scheduled templates"
                description="Templates with a frequency appear here for manual generation."
              />
              <div v-else class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Template</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Frequency</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead class="text-right">Action</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="template in scheduledTemplates" :key="`s-${template.id}`">
                      <TableCell class="font-medium">{{ template.name }}</TableCell>
                      <TableCell>{{ categoryLabel(template.category) }}</TableCell>
                      <TableCell class="capitalize">{{ template.frequency }}</TableCell>
                      <TableCell>
                        <Badge
                          variant="outline"
                          :class="cn('capitalize', statusBadgeClass(templateStatus(template)))"
                        >
                          {{ templateStatus(template) }}
                        </Badge>
                      </TableCell>
                      <TableCell class="text-right">
                        <Button
                          size="sm"
                          :disabled="generatingId === template.id"
                          @click="generateTemplate(template, true)"
                        >
                          Generate
                        </Button>
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>

    <FormSheet
      v-model:open="createOpen"
      title="Create report template"
      description="Add a reusable template. Generation is available for academic, attendance, finance, and payroll categories."
      :fields="createFields"
      :schema="createSchema"
      :saving="createSaving"
      save-label="Create template"
      @submit="onCreateSubmit"
    />
  </PageShell>
</template>
