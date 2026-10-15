<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowDownLeft,
  ArrowUpRight,
  Ban,
  FileSpreadsheet,
  LineChart,
  Printer,
  RefreshCw,
  Search,
  Wallet,
  Zap,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
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
import { toast } from 'vue-sonner'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { formatMoney } from '@/lib/finance-constants'
import { cn } from '@/lib/utils'
import { financeApi } from '@/services/api.service'

interface TxStudent {
  id?: number
  full_name?: string
  student_number?: string
  class?: string | { name?: string } | null
}

interface TransactionRow {
  id: number
  type?: string
  category?: string
  description?: string
  reference?: string
  debit?: string | number
  credit?: string | number
  balance?: string | number
  currency?: string
  status?: string
  student_id?: number | null
  student?: TxStudent | null
  payroll?: {
    employee_name?: string
    employee_number?: string
    period?: string
  } | null
  created_at?: string
}

interface TxSummary {
  total_transactions?: number
  total_debit_usd?: number
  total_credit_usd?: number
  total_debit_zwl?: number
  total_credit_zwl?: number
  net_balance_usd?: number
  net_balance_zwl?: number
}

const route = useRoute()
const loading = ref(true)
const error = ref<string | null>(null)
const rows = ref<TransactionRow[]>([])
const summary = ref<TxSummary | null>(null)
const reconciliation = ref<Record<string, unknown> | null>(null)
const reconLoading = ref(false)
const activeTab = ref('all')
const searchQuery = ref('')
const studentFilter = ref('all')
const typeFilter = ref('all')
const currencyFilter = ref('all')
const statusFilter = ref('all')
const fromDate = ref('')
const toDate = ref('')
const serverPage = ref(1)
const serverPageCount = ref(1)
const serverTotal = ref(0)
const perPage = 25
let searchTimer: ReturnType<typeof setTimeout> | undefined

const TYPE_OPTIONS = [
  { value: 'payment', label: 'Payment' },
  { value: 'fee_applied', label: 'Fee Applied' },
  { value: 'expense', label: 'Expense' },
  { value: 'income', label: 'Income' },
  { value: 'reversal', label: 'Reversal' },
]

const STATUS_OPTIONS = [
  { value: 'completed', label: 'Completed' },
  { value: 'pending', label: 'Pending' },
  { value: 'reversed', label: 'Reversed' },
]

const studentOptions = computed(() => {
  const map = new Map<string, string>()
  for (const row of rows.value) {
    if (!row.student_id || !row.student) continue
    const name = row.student.full_name || `Student #${row.student_id}`
    map.set(String(row.student_id), name)
  }
  return [...map.entries()]
    .map(([value, label]) => ({ value, label }))
    .sort((a, b) => a.label.localeCompare(b.label))
})

const todayCount = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return rows.value.filter((r) => String(r.created_at ?? '').startsWith(today)).length
})

const filteredRows = computed(() => {
  let list = [...rows.value]
  if (activeTab.value === 'by-student') {
    list = list.filter((r) => r.student_id != null)
  }
  return list
})

const byStudentGroups = computed(() => {
  const map = new Map<string, { name: string; classLabel: string; rows: TransactionRow[]; debit: number; credit: number }>()
  for (const row of filteredRows.value) {
    if (!row.student_id) continue
    const key = String(row.student_id)
    const existing = map.get(key)
    const name = row.student?.full_name || `Student #${row.student_id}`
    const classLabel = studentClass(row)
    if (!existing) {
      map.set(key, {
        name,
        classLabel,
        rows: [row],
        debit: num(row.debit),
        credit: num(row.credit),
      })
    } else {
      existing.rows.push(row)
      existing.debit += num(row.debit)
      existing.credit += num(row.credit)
    }
  }
  return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
})

const byTypeGroups = computed(() => {
  const map = new Map<string, { type: string; count: number; debit: number; credit: number }>()
  for (const row of filteredRows.value) {
    const type = row.type || 'other'
    const existing = map.get(type)
    if (!existing) {
      map.set(type, { type, count: 1, debit: num(row.debit), credit: num(row.credit) })
    } else {
      existing.count += 1
      existing.debit += num(row.debit)
      existing.credit += num(row.credit)
    }
  }
  return [...map.values()].sort((a, b) => b.count - a.count)
})

function num(value: unknown) {
  const n = Number(value ?? 0)
  return Number.isFinite(n) ? n : 0
}

function money(amount: unknown, currency = 'USD') {
  return formatMoney(amount, currency)
}

function compactMoney(amount: unknown, currency: string) {
  const n = num(amount)
  if (currency === 'ZWL' && Math.abs(n) >= 1000) {
    const k = n / 1000
    const label = Number.isInteger(k) ? `${k}k` : `${k.toFixed(1)}k`
    return `ZWL$ ${label}`
  }
  if (currency === 'USD') return `$${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`
  return formatMoney(n, currency)
}

function studentClass(row: TransactionRow) {
  const c = row.student?.class
  if (!c) return '—'
  if (typeof c === 'string') return c
  return c.name || '—'
}

function partyName(row: TransactionRow) {
  if (row.student?.full_name) return row.student.full_name
  if (row.payroll?.employee_name) return row.payroll.employee_name
  return '—'
}

function partySub(row: TransactionRow) {
  if (row.student) return studentClass(row)
  if (row.payroll?.period) return row.payroll.period
  return row.category || '—'
}

function typeMeta(type?: string) {
  switch (String(type ?? '').toLowerCase()) {
    case 'payment':
      return { label: 'Payment', icon: ArrowDownLeft, class: 'text-emerald-600 bg-emerald-500/10' }
    case 'fee_applied':
      return { label: 'Fee Applied', icon: Zap, class: 'text-amber-600 bg-amber-500/10' }
    case 'reversal':
      return { label: 'Reversal', icon: Ban, class: 'text-destructive bg-destructive/10' }
    case 'expense':
      return { label: 'Expense', icon: ArrowUpRight, class: 'text-rose-600 bg-rose-500/10' }
    case 'income':
      return { label: 'Income', icon: ArrowDownLeft, class: 'text-sky-600 bg-sky-500/10' }
    default:
      return { label: type || 'Other', icon: RefreshCw, class: 'text-muted-foreground bg-muted' }
  }
}

function listParams(page = serverPage.value) {
  const params: Record<string, string | number | boolean> = {
    page,
    per_page: perPage,
  }
  if (studentFilter.value !== 'all') params.student_id = studentFilter.value
  if (typeFilter.value !== 'all') params.type = typeFilter.value
  if (currencyFilter.value !== 'all') params.currency = currencyFilter.value
  if (statusFilter.value !== 'all') params.status = statusFilter.value
  if (fromDate.value) params.from = fromDate.value
  if (toDate.value) params.to = `${toDate.value} 23:59:59`
  if (searchQuery.value.trim()) params.search = searchQuery.value.trim()
  if (route.query.payroll_id) params.payroll_id = String(route.query.payroll_id)
  if (route.query.category) params.category = String(route.query.category)
  if (route.query.type && typeFilter.value === 'all') params.type = String(route.query.type)
  return params
}

async function load(page = serverPage.value) {
  loading.value = true
  error.value = null
  try {
    const params = listParams(page)
    const [list, sum] = await Promise.all([
      financeApi.transactions.list(params),
      financeApi.transactions.summary({
        ...(fromDate.value ? { from: fromDate.value } : {}),
        ...(toDate.value ? { to: `${toDate.value} 23:59:59` } : {}),
      }) as Promise<TxSummary>,
    ])
    rows.value = list.data as TransactionRow[]
    serverPage.value = list.current_page
    serverPageCount.value = list.last_page
    serverTotal.value = list.total
    summary.value = sum
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load transactions')
  } finally {
    loading.value = false
  }
}

function onServerPageChange(page: number) {
  if (page < 1 || page > serverPageCount.value) return
  void load(page)
}

async function loadReconciliation() {
  reconLoading.value = true
  try {
    reconciliation.value = await financeApi.reconciliation({ period: 'monthly' })
  } catch {
    reconciliation.value = null
  } finally {
    reconLoading.value = false
  }
}

function exportCsv() {
  if (!rows.value.length && serverTotal.value === 0) {
    toast.warning('Nothing to export')
    return
  }
  void (async () => {
    try {
      const filters = { ...listParams(1) }
      delete filters.page
      delete filters.per_page
      const exportRows = (await financeApi.transactions.listAll(filters)) as TransactionRow[]
      if (!exportRows.length) {
        toast.warning('Nothing to export')
        return
      }
      const header = [
        'Date',
        'Type',
        'Party',
        'Description',
        'Reference',
        'Debit',
        'Credit',
        'Balance',
        'Currency',
        'Status',
      ]
      const lines = exportRows.map((row) =>
        [
          row.created_at ?? '',
          row.type ?? '',
          partyName(row),
          row.description ?? '',
          row.reference ?? '',
          num(row.debit),
          num(row.credit),
          num(row.balance),
          row.currency ?? 'USD',
          row.status ?? '',
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
      a.download = `transactions-${new Date().toISOString().slice(0, 10)}.csv`
      a.click()
      URL.revokeObjectURL(url)
      toast.success('Transactions exported')
    } catch (err) {
      toast.error(getErrorMessage(err, 'Export failed'))
    }
  })()
}

function printLedger() {
  window.print()
}

watch(
  [studentFilter, typeFilter, currencyFilter, statusFilter, fromDate, toDate],
  () => {
    serverPage.value = 1
    void load(1)
  },
)

watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    serverPage.value = 1
    void load(1)
  }, 300)
})

watch(activeTab, (tab) => {
  if (tab === 'reconciliation') void loadReconciliation()
})

onMounted(() => {
  if (route.query.type) typeFilter.value = String(route.query.type)
  void load()
})
</script>

<template>
  <PageShell
    title="Financial Transactions"
    description="Complete audit trail of all financial activities"
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" :disabled="!filteredRows.length" @click="printLedger">
        <Printer class="mr-2 size-4" aria-hidden="true" />
        Print Ledger
      </Button>
      <Button variant="outline" :disabled="!filteredRows.length" @click="exportCsv">
        <FileSpreadsheet class="mr-2 size-4" aria-hidden="true" />
        Export CSV
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading transaction ledger…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-6 print:space-y-4">
      <section
        aria-labelledby="txn-kpis"
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6"
      >
        <h2 id="txn-kpis" class="sr-only">Transaction summary</h2>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Total Transactions</p>
              <p class="text-2xl font-semibold tabular-nums">{{ summary?.total_transactions ?? rows.length }}</p>
              <p class="text-xs text-muted-foreground">{{ todayCount }} today</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <LineChart class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Debits (USD)</p>
              <p class="text-2xl font-semibold tabular-nums text-destructive">
                {{ compactMoney(summary?.total_debit_usd, 'USD') }}
              </p>
              <p class="text-xs text-muted-foreground">Total charges</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
              <ArrowUpRight class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Credits (USD)</p>
              <p class="text-2xl font-semibold tabular-nums text-emerald-600">
                {{ compactMoney(summary?.total_credit_usd, 'USD') }}
              </p>
              <p class="text-xs text-muted-foreground">Total payments</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
              <ArrowDownLeft class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Net Balance (USD)</p>
              <p
                class="text-2xl font-semibold tabular-nums"
                :class="num(summary?.net_balance_usd) > 0 ? 'text-destructive' : 'text-foreground'"
              >
                {{ compactMoney(summary?.net_balance_usd, 'USD') }}
              </p>
              <p class="text-xs text-muted-foreground">Outstanding</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
              <Wallet class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Debits (ZWL)</p>
              <p class="text-2xl font-semibold tabular-nums text-destructive">
                {{ compactMoney(summary?.total_debit_zwl, 'ZWL') }}
              </p>
              <p class="text-xs text-muted-foreground">Total charges</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
              <ArrowUpRight class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70">
          <CardContent class="flex items-start justify-between gap-3 px-5 py-5">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">Credits (ZWL)</p>
              <p class="text-2xl font-semibold tabular-nums text-emerald-600">
                {{ compactMoney(summary?.total_credit_zwl, 'ZWL') }}
              </p>
              <p class="text-xs text-muted-foreground">Total payments</p>
            </div>
            <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
              <ArrowDownLeft class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>
      </section>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Transaction views" class="flex h-auto flex-wrap">
          <TabsTrigger value="all">All Transactions</TabsTrigger>
          <TabsTrigger value="by-student">By Student</TabsTrigger>
          <TabsTrigger value="by-type">By Type</TabsTrigger>
          <TabsTrigger value="reconciliation">Reconciliation</TabsTrigger>
        </TabsList>

        <TabsContent value="all" class="space-y-4">
          <Card class="overflow-hidden border-border/70 shadow-sm">
            <CardHeader class="border-b border-border/60 pb-4">
              <div class="space-y-4">
                <div>
                  <CardTitle class="text-base">Transaction Ledger</CardTitle>
                  <CardDescription>Complete record of all financial transactions.</CardDescription>
                </div>

                <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                  <div class="relative xl:col-span-2">
                    <Search
                      class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                      aria-hidden="true"
                    />
                    <Input
                      v-model="searchQuery"
                      type="search"
                      class="h-10 pl-9"
                      placeholder="Search transactions…"
                      aria-label="Search transactions"
                    />
                  </div>

                  <Select v-model="studentFilter">
                    <SelectTrigger class="h-10" aria-label="Filter by student">
                      <SelectValue placeholder="All Students" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Students</SelectItem>
                      <SelectItem
                        v-for="opt in studentOptions"
                        :key="opt.value"
                        :value="opt.value"
                      >
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="typeFilter">
                    <SelectTrigger class="h-10" aria-label="Filter by type">
                      <SelectValue placeholder="All Types" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Types</SelectItem>
                      <SelectItem v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="currencyFilter">
                    <SelectTrigger class="h-10" aria-label="Filter by currency">
                      <SelectValue placeholder="All Currencies" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Currencies</SelectItem>
                      <SelectItem value="USD">USD</SelectItem>
                      <SelectItem value="ZWL">ZWL</SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="statusFilter">
                    <SelectTrigger class="h-10" aria-label="Filter by status">
                      <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Status</SelectItem>
                      <SelectItem v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <div class="space-y-1">
                    <Label for="txn-from" class="sr-only">From date</Label>
                    <DatePicker id="txn-from" v-model="fromDate" placeholder="From date" />
                  </div>
                  <div class="space-y-1">
                    <Label for="txn-to" class="sr-only">To date</Label>
                    <DatePicker id="txn-to" v-model="toDate" placeholder="To date" />
                  </div>
                </div>
              </div>
            </CardHeader>

            <CardContent class="p-0">
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date &amp; Time</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Student</TableHead>
                      <TableHead>Description</TableHead>
                      <TableHead>Reference</TableHead>
                      <TableHead class="text-right">Debit</TableHead>
                      <TableHead class="text-right">Credit</TableHead>
                      <TableHead class="text-right">Balance</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <template v-if="filteredRows.length">
                    <TableRow v-for="row in filteredRows" :key="row.id">
                      <TableCell class="whitespace-nowrap">
                        {{ formatDateTime(row.created_at) }}
                      </TableCell>
                      <TableCell>
                        <div class="flex items-center gap-2">
                          <span
                            :class="cn(
                              'inline-flex size-7 items-center justify-center rounded-full',
                              typeMeta(row.type).class,
                            )"
                          >
                            <component :is="typeMeta(row.type).icon" class="size-3.5" aria-hidden="true" />
                          </span>
                          <span class="text-sm font-medium">{{ typeMeta(row.type).label }}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div class="min-w-0">
                          <p class="truncate font-medium">{{ partyName(row) }}</p>
                          <p class="truncate text-xs text-muted-foreground">{{ partySub(row) }}</p>
                        </div>
                      </TableCell>
                      <TableCell class="max-w-[16rem] truncate text-sm">
                        {{ row.description || '—' }}
                      </TableCell>
                      <TableCell class="font-mono text-xs text-muted-foreground">
                        {{ row.reference || '—' }}
                      </TableCell>
                      <TableCell class="text-right tabular-nums text-destructive">
                        <span v-if="num(row.debit)">{{ money(row.debit, row.currency) }}</span>
                        <span v-else class="text-muted-foreground">—</span>
                      </TableCell>
                      <TableCell class="text-right tabular-nums text-emerald-600">
                        <span v-if="num(row.credit)">{{ money(row.credit, row.currency) }}</span>
                        <span v-else class="text-muted-foreground">—</span>
                      </TableCell>
                      <TableCell class="text-right font-semibold tabular-nums">
                        {{ money(row.balance, row.currency) }}
                      </TableCell>
                    </TableRow>
                    </template>
                    <TableEmpty
                      v-else
                      :colspan="8"
                      title="No matching transactions"
                      description="Try adjusting search, type, or date filters."
                    />
                  </TableBody>
                </Table>
              </div>
              <div
                v-if="serverPageCount > 1"
                class="flex flex-col gap-3 border-t border-border/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <p class="text-sm text-muted-foreground">
                  Page {{ serverPage }} of {{ serverPageCount }} · {{ serverTotal }} transactions
                </p>
                <div class="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="serverPage <= 1 || loading"
                    @click="onServerPageChange(serverPage - 1)"
                  >
                    Previous
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="serverPage >= serverPageCount || loading"
                    @click="onServerPageChange(serverPage + 1)"
                  >
                    Next
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="by-student">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">By student</CardTitle>
              <CardDescription>Student-linked ledger activity for the current filters.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <p v-if="!byStudentGroups.length" class="py-8 text-center text-sm text-muted-foreground">
                No student transactions in this view.
              </p>
              <div
                v-for="group in byStudentGroups"
                :key="group.name"
                class="flex flex-col gap-2 rounded-xl border border-border/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <div>
                  <p class="font-medium">{{ group.name }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ group.classLabel }} · {{ group.rows.length }} transactions
                  </p>
                </div>
                <div class="flex gap-4 text-sm tabular-nums">
                  <span class="text-destructive">Dr {{ money(group.debit) }}</span>
                  <span class="text-emerald-600">Cr {{ money(group.credit) }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="by-type">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">By type</CardTitle>
              <CardDescription>Totals grouped by transaction type.</CardDescription>
            </CardHeader>
            <CardContent class="p-0">
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Type</TableHead>
                      <TableHead>Count</TableHead>
                      <TableHead class="text-right">Debit</TableHead>
                      <TableHead class="text-right">Credit</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <template v-if="byTypeGroups.length">
                    <TableRow v-for="group in byTypeGroups" :key="group.type">
                      <TableCell>
                        <div class="flex items-center gap-2">
                          <span
                            :class="cn(
                              'inline-flex size-7 items-center justify-center rounded-full',
                              typeMeta(group.type).class,
                            )"
                          >
                            <component :is="typeMeta(group.type).icon" class="size-3.5" aria-hidden="true" />
                          </span>
                          {{ typeMeta(group.type).label }}
                        </div>
                      </TableCell>
                      <TableCell class="tabular-nums">{{ group.count }}</TableCell>
                      <TableCell class="text-right tabular-nums text-destructive">
                        {{ money(group.debit) }}
                      </TableCell>
                    <TableCell class="text-right tabular-nums text-emerald-600">
                      {{ money(group.credit) }}
                    </TableCell>
                  </TableRow>
                    </template>
                    <TableEmpty
                      v-else
                      :colspan="4"
                      title="No transactions to group"
                      description="Adjust filters or add ledger activity to see totals by type."
                    />
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="reconciliation">
          <Card class="border-border/70 shadow-sm">
            <CardHeader>
              <CardTitle class="text-base">Period reconciliation</CardTitle>
              <CardDescription>
                Monthly cash reconciliation from payments and payroll outflows.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <PageLoader v-if="reconLoading" class="py-12" label="Loading reconciliation…" />
              <template v-else-if="reconciliation">
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  <div
                    v-for="(value, key) in reconciliation"
                    :key="String(key)"
                    class="rounded-xl border border-border/60 px-4 py-3"
                  >
                    <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                      {{ String(key).replaceAll('_', ' ') }}
                    </dt>
                    <dd class="mt-1 text-lg font-semibold tabular-nums">
                      {{ typeof value === 'number' ? money(value) : String(value ?? '—') }}
                    </dd>
                  </div>
                </dl>
              </template>
              <p v-else class="py-8 text-center text-sm text-muted-foreground">
                Could not load reconciliation data.
              </p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </PageShell>
</template>
