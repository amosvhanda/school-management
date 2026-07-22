<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
  AlertCircle,
  Download,
  MoreHorizontal,
  Plus,
  Search,
  DollarSign,
  Users,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Progress } from '@/components/ui/progress'
import { toast } from 'vue-sonner'
import { getErrorMessage } from '@/lib/api-response'
import { formatMoney } from '@/lib/finance-constants'
import { cn } from '@/lib/utils'
import { financeApi } from '@/services/api.service'
import { payrollProcessPromptForm } from '@/modules/shared/action-prompt-forms'

interface PayrollRow {
  id: number
  employee_name?: string
  employee_number?: string
  period?: string
  month?: number
  year?: number
  base_salary?: string | number
  allowances_total?: string | number
  deductions_total?: string | number
  gross_salary?: string | number
  net_salary?: string | number
  amount_paid?: string | number
  remaining_balance?: string | number
  currency?: string
  status?: string
  teacher?: {
    id?: number
    name?: string
    employee_id?: string
    department?: string
    subject?: string
  } | null
}

interface PayrollSummary {
  total_payroll?: number
  total_paid?: number
  total_pending?: number
  total_partial?: number
  total_employees?: number
  paid_employees?: number
  pending_employees?: number
  partial_employees?: number
}

interface TrendPoint {
  month?: string
  year?: number
  totalPayroll?: number
  employees?: number
}

interface DeptPoint {
  name?: string
  value?: number
}

const now = new Date()
const month = ref(String(now.getMonth() + 1))
const year = ref(String(now.getFullYear()))
const loading = ref(true)
const error = ref<string | null>(null)
const rows = ref<PayrollRow[]>([])
const summary = ref<PayrollSummary | null>(null)
const trends = ref<TrendPoint[]>([])
const departments = ref<DeptPoint[]>([])
const activeTab = ref('current')
const searchQuery = ref('')
const departmentFilter = ref('all')
const generating = ref(false)
const processOpen = ref(false)
const processSaving = ref(false)
const processTarget = ref<PayrollRow | null>(null)
const processKey = ref(0)
const processReset = ref<Record<string, unknown>>({})

const monthLabel = computed(() => {
  const d = new Date(Number(year.value), Number(month.value) - 1, 1)
  return d.toLocaleString(undefined, { month: 'long', year: 'numeric' })
})

const currency = computed(() => rows.value[0]?.currency ?? 'USD')

const departmentOptions = computed(() => {
  const set = new Set<string>()
  for (const row of rows.value) {
    const dept = row.teacher?.department?.trim()
    if (dept) set.add(dept)
  }
  return [...set].sort((a, b) => a.localeCompare(b))
})

const filteredRows = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return rows.value.filter((row) => {
    const dept = row.teacher?.department ?? ''
    if (departmentFilter.value !== 'all' && dept !== departmentFilter.value) return false
    if (!q) return true
    const hay = [
      row.employee_name,
      row.employee_number,
      row.teacher?.name,
      row.teacher?.employee_id,
      row.teacher?.subject,
      dept,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return hay.includes(q)
  })
})

const historyRows = computed(() =>
  [...rows.value].sort((a, b) => {
    const ay = Number(a.year ?? 0)
    const by = Number(b.year ?? 0)
    if (ay !== by) return by - ay
    return Number(b.month ?? 0) - Number(a.month ?? 0)
  }),
)

const kpis = computed(() => {
  const s = summary.value
  const totalEmployees = Number(s?.total_employees ?? rows.value.length)
  const paid = Number(s?.paid_employees ?? 0)
  const pending = Number(s?.pending_employees ?? 0) + Number(s?.partial_employees ?? 0)
  const totalPayroll = Number(s?.total_payroll ?? 0)
  const avg = totalEmployees > 0 ? totalPayroll / totalEmployees : 0
  return {
    totalPayroll,
    paid,
    totalEmployees,
    pending,
    avg,
  }
})

const maxTrend = computed(() =>
  Math.max(1, ...trends.value.map((t) => Number(t.totalPayroll ?? 0))),
)

const monthOptions = Array.from({ length: 12 }, (_, i) => ({
  value: String(i + 1),
  label: new Date(2026, i, 1).toLocaleString(undefined, { month: 'long' }),
}))

const yearOptions = computed(() => {
  const y = now.getFullYear()
  return [String(y - 1), String(y), String(y + 1)]
})

function money(amount: unknown) {
  return formatMoney(amount, currency.value)
}

function num(value: unknown) {
  const n = Number(value ?? 0)
  return Number.isFinite(n) ? n : 0
}

function initials(name?: string) {
  const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return '??'
  return parts
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase() ?? '')
    .join('')
}

function employeeSubtitle(row: PayrollRow) {
  const id = row.employee_number || row.teacher?.employee_id || '—'
  const role = row.teacher?.subject || row.teacher?.department || 'Staff'
  return `${id} · ${role}`
}

function statusClass(status?: string) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'paid') {
    return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300'
  }
  if (s === 'partial') {
    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200'
  }
  if (s === 'cancelled') {
    return 'border-border bg-muted text-muted-foreground'
  }
  return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200'
}

function canProcess(row: PayrollRow) {
  const s = String(row.status ?? '').toLowerCase()
  return s === 'pending' || s === 'partial'
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const params = { month: month.value, year: year.value }
    const [list, sum, trendList, dept] = await Promise.all([
      financeApi.payroll.list(params) as Promise<PayrollRow[]>,
      financeApi.payroll.summary(params) as Promise<PayrollSummary>,
      financeApi.payroll.trends({ months: 6 }) as Promise<TrendPoint[]>,
      financeApi.payroll.departmentSummary(params) as Promise<DeptPoint[] | { data?: DeptPoint[] }>,
    ])
    rows.value = list
    summary.value = sum
    trends.value = trendList
    departments.value = Array.isArray(dept) ? dept : ((dept as { data?: DeptPoint[] })?.data ?? [])
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load payroll')
  } finally {
    loading.value = false
  }
}

async function generatePayroll() {
  generating.value = true
  try {
    const result = (await financeApi.payroll.generate({
      month: Number(month.value),
      year: Number(year.value),
    })) as { created?: number; errors?: string[] }
    if (result?.errors?.length) {
      toast.warning('Payroll generated with warnings', {
        description: result.errors.slice(0, 2).join(' '),
      })
    } else {
      toast.success('Payroll generated', {
        description: `${result?.created ?? 0} payslip(s) for ${monthLabel.value}`,
      })
    }
    await load()
  } catch (err) {
    toast.error('Generate failed', { description: getErrorMessage(err) })
  } finally {
    generating.value = false
  }
}

function openProcess(row: PayrollRow) {
  processTarget.value = row
  processReset.value = {
    payment_method: 'bank_transfer',
    amount_paid: num(row.remaining_balance) || num(row.net_salary),
    payment_reference: '',
    paid_at: new Date().toISOString().slice(0, 10),
  }
  processKey.value += 1
  processOpen.value = true
}

async function onProcessSubmit(values: Record<string, unknown>) {
  if (!processTarget.value) return
  processSaving.value = true
  try {
    await financeApi.payroll.process(processTarget.value.id, {
      payment_method: values.payment_method,
      amount_paid: Number(values.amount_paid),
      payment_reference: values.payment_reference || null,
      paid_at: values.paid_at || null,
    })
    toast.success('Payment recorded')
    processOpen.value = false
    await load()
  } catch (err) {
    toast.error('Process failed', { description: getErrorMessage(err) })
  } finally {
    processSaving.value = false
  }
}

function exportCsv() {
  if (!filteredRows.value.length) {
    toast.warning('Nothing to export')
    return
  }
  const header = [
    'Employee',
    'Employee Number',
    'Department',
    'Base Salary',
    'Allowances',
    'Deductions',
    'Gross Pay',
    'Net Pay',
    'Status',
    'Period',
  ]
  const lines = filteredRows.value.map((row) =>
    [
      row.employee_name ?? '',
      row.employee_number ?? row.teacher?.employee_id ?? '',
      row.teacher?.department ?? '',
      num(row.base_salary),
      num(row.allowances_total),
      num(row.deductions_total),
      num(row.gross_salary),
      num(row.net_salary),
      row.status ?? '',
      row.period ?? monthLabel.value,
    ]
      .map((v) => `"${String(v).replaceAll('"', '""')}"`)
      .join(','),
  )
  const blob = new Blob([[header.join(','), ...lines].join('\n')], {
    type: 'text/csv;charset=utf-8',
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `payroll-${year.value}-${month.value.padStart(2, '0')}.csv`
  a.click()
  URL.revokeObjectURL(url)
  toast.success('Payroll exported')
}

watch([month, year], () => {
  void load()
})

onMounted(load)
</script>

<template>
  <PageShell
    title="Payroll Management"
    description="Manage staff salaries, allowances, and payment processing"
    max-width="wide"
  >
    <template #actions>
      <div class="flex flex-wrap items-end gap-2">
        <div class="space-y-1">
          <Label for="payroll-month" class="sr-only">Month</Label>
          <Select v-model="month">
            <SelectTrigger id="payroll-month" class="h-10 w-[9.5rem]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in monthOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1">
          <Label for="payroll-year" class="sr-only">Year</Label>
          <Select v-model="year">
            <SelectTrigger id="payroll-year" class="h-10 w-[6.5rem]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="y in yearOptions" :key="y" :value="y">{{ y }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button variant="outline" :disabled="!filteredRows.length" @click="exportCsv">
          <Download class="mr-2 size-4" aria-hidden="true" />
          Export Payroll
        </Button>
        <Button :disabled="generating" :aria-busy="generating" @click="generatePayroll">
          <Plus class="mr-2 size-4" aria-hidden="true" />
          {{ generating ? 'Processing…' : 'Process Payroll' }}
        </Button>
      </div>
    </template>

    <PageLoader v-if="loading" label="Loading payroll workspace…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-6">
      <section aria-labelledby="payroll-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="payroll-kpis" class="sr-only">Payroll summary</h2>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Total Payroll
              </p>
              <p class="text-2xl font-semibold tabular-nums tracking-tight">
                {{ money(kpis.totalPayroll) }}
              </p>
              <p class="text-xs text-muted-foreground">This month</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <DollarSign class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Employees Paid
              </p>
              <p class="text-2xl font-semibold tabular-nums tracking-tight text-emerald-600">
                {{ kpis.paid }}
              </p>
              <p class="text-xs text-muted-foreground">Out of {{ kpis.totalEmployees }} total</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
              <Users class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Pending Payments
              </p>
              <p class="text-2xl font-semibold tabular-nums tracking-tight text-amber-600">
                {{ kpis.pending }}
              </p>
              <p class="text-xs text-muted-foreground">Awaiting processing</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
              <AlertCircle class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Average Salary
              </p>
              <p class="text-2xl font-semibold tabular-nums tracking-tight">
                {{ money(kpis.avg) }}
              </p>
              <p class="text-xs text-muted-foreground">Per employee</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <DollarSign class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>
      </section>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Payroll views">
          <TabsTrigger value="current">Current Payroll</TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
          <TabsTrigger value="history">Payment History</TabsTrigger>
        </TabsList>

        <TabsContent value="current">
          <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader class="border-b border-border/60 pb-4">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                  <CardTitle class="text-base">Payroll for {{ monthLabel }}</CardTitle>
                  <CardDescription>Current month payroll status and details.</CardDescription>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <div class="relative w-full sm:w-64">
                    <Search
                      class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                      aria-hidden="true"
                    />
                    <Input
                      v-model="searchQuery"
                      type="search"
                      class="h-10 pl-9"
                      placeholder="Search employees…"
                      aria-label="Search employees"
                    />
                  </div>
                  <Select v-model="departmentFilter">
                    <SelectTrigger class="h-10 w-full sm:w-48" aria-label="Filter by department">
                      <SelectValue placeholder="All Departments" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Departments</SelectItem>
                      <SelectItem
                        v-for="dept in departmentOptions"
                        :key="dept"
                        :value="dept"
                      >
                        {{ dept }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>

            <CardContent class="p-0">
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Employee</TableHead>
                      <TableHead>Base Salary</TableHead>
                      <TableHead>Allowances</TableHead>
                      <TableHead>Deductions</TableHead>
                      <TableHead>Gross Pay</TableHead>
                      <TableHead>Net Pay</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead class="w-12 text-right">
                        <span class="sr-only">Actions</span>
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <template v-if="filteredRows.length">
                    <TableRow v-for="row in filteredRows" :key="row.id">
                      <TableCell>
                        <div class="flex items-center gap-3">
                          <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                            aria-hidden="true"
                          >
                            {{ initials(row.employee_name || row.teacher?.name) }}
                          </div>
                          <div class="min-w-0">
                            <p class="truncate font-medium text-foreground">
                              {{ row.employee_name || row.teacher?.name || 'Staff' }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                              {{ employeeSubtitle(row) }}
                            </p>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell class="tabular-nums">{{ money(row.base_salary) }}</TableCell>
                      <TableCell class="tabular-nums text-emerald-600">
                        +{{ money(row.allowances_total) }}
                      </TableCell>
                      <TableCell class="tabular-nums text-destructive">
                        -{{ money(row.deductions_total) }}
                      </TableCell>
                      <TableCell class="tabular-nums">{{ money(row.gross_salary) }}</TableCell>
                      <TableCell class="font-semibold tabular-nums">
                        {{ money(row.net_salary) }}
                      </TableCell>
                      <TableCell>
                        <Badge
                          variant="outline"
                          :class="cn('capitalize font-medium', statusClass(row.status))"
                        >
                          {{ row.status === 'partial' ? 'Pending' : (row.status ?? 'pending') }}
                        </Badge>
                      </TableCell>
                      <TableCell class="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button
                              variant="ghost"
                              size="icon"
                              class="size-8"
                              :aria-label="`Actions for ${row.employee_name ?? 'employee'}`"
                            >
                              <MoreHorizontal class="size-4" aria-hidden="true" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              :disabled="!canProcess(row)"
                              @click="openProcess(row)"
                            >
                              Record payment
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              @click="
                                toast.message('Payslip', {
                                  description: `${row.employee_name} · ${money(row.net_salary)} net`,
                                })
                              "
                            >
                              View payslip summary
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                    </template>
                    <TableEmpty
                      v-else
                      :colspan="8"
                      :title="rows.length ? 'No matching employees' : 'No payroll for this period'"
                      :description="rows.length
                        ? 'Try adjusting search or status filters.'
                        : 'Use Process Payroll to generate payslips for this month.'"
                    />
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" class="space-y-4">
          <div class="grid gap-4 lg:grid-cols-2">
            <Card class="border-border/70 shadow-sm">
              <CardHeader>
                <CardTitle class="text-base">Payroll trends</CardTitle>
                <CardDescription>Gross payroll over recent months.</CardDescription>
              </CardHeader>
              <CardContent class="space-y-4">
                <div v-for="(point, i) in trends" :key="`${point.month}-${point.year}-${i}`" class="space-y-1.5">
                  <div class="flex items-center justify-between text-sm">
                    <span>{{ point.month }} {{ point.year }}</span>
                    <span class="font-medium tabular-nums">{{ money(point.totalPayroll) }}</span>
                  </div>
                  <Progress :model-value="(num(point.totalPayroll) / maxTrend) * 100" class="h-2" />
                </div>
                <p v-if="!trends.length" class="text-sm text-muted-foreground">No trend data yet.</p>
              </CardContent>
            </Card>

            <Card class="border-border/70 shadow-sm">
              <CardHeader>
                <CardTitle class="text-base">By department</CardTitle>
                <CardDescription>Net payroll for {{ monthLabel }}.</CardDescription>
              </CardHeader>
              <CardContent class="space-y-3">
                <div
                  v-for="dept in departments"
                  :key="dept.name"
                  class="flex items-center justify-between rounded-xl border border-border/60 px-4 py-3"
                >
                  <span class="text-sm font-medium">{{ dept.name ?? 'Unassigned' }}</span>
                  <span class="text-sm font-semibold tabular-nums">{{ money(dept.value) }}</span>
                </div>
                <p v-if="!departments.length" class="text-sm text-muted-foreground">
                  No department breakdown for this period.
                </p>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="history">
          <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Payment history</CardTitle>
              <CardDescription>
                Payslips for {{ monthLabel }}. Change month/year above to review other periods.
              </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Employee</TableHead>
                      <TableHead>Period</TableHead>
                      <TableHead>Net Pay</TableHead>
                      <TableHead>Paid</TableHead>
                      <TableHead>Remaining</TableHead>
                      <TableHead>Status</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <template v-if="historyRows.length">
                    <TableRow v-for="row in historyRows" :key="`h-${row.id}`">
                      <TableCell class="font-medium">
                        {{ row.employee_name || row.teacher?.name || '—' }}
                      </TableCell>
                      <TableCell>{{ row.period ?? monthLabel }}</TableCell>
                      <TableCell class="font-semibold tabular-nums">
                        {{ money(row.net_salary) }}
                      </TableCell>
                      <TableCell class="tabular-nums">{{ money(row.amount_paid) }}</TableCell>
                      <TableCell class="tabular-nums">{{ money(row.remaining_balance) }}</TableCell>
                      <TableCell>
                        <Badge
                          variant="outline"
                          :class="cn('capitalize font-medium', statusClass(row.status))"
                        >
                          {{ row.status ?? 'pending' }}
                        </Badge>
                      </TableCell>
                    </TableRow>
                    </template>
                    <TableEmpty
                      v-else
                      :colspan="6"
                      title="No payroll history"
                      description="No payslips for this period. Change month or year above to review other periods."
                    />
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>

    <FormSheet
      v-model:open="processOpen"
      :title="payrollProcessPromptForm.title"
      :description="payrollProcessPromptForm.description"
      :fields="payrollProcessPromptForm.fields"
      :schema="payrollProcessPromptForm.schema"
      :reset-values="processReset"
      :form-key="String(processKey)"
      :saving="processSaving"
      :save-label="payrollProcessPromptForm.saveLabel"
      :size="payrollProcessPromptForm.size"
      @submit="onProcessSubmit"
    />
  </PageShell>
</template>
