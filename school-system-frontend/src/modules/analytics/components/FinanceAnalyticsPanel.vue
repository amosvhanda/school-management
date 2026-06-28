<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Banknote, Receipt, TrendingUp, Wallet } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { formatMoney, formatPaymentMethod } from '@/lib/finance-constants'
import type {
  AgingReport,
  FeeCollectionPoint,
  FinancialReport,
  FinanceSummary,
  ReconciliationReport,
} from '@/modules/analytics/types/finance-analytics'
import { INVOICE_STATUS_LABELS } from '@/modules/analytics/types/finance-analytics'

const props = defineProps<{
  loading?: boolean
  summary: FinanceSummary | null
  financial: FinancialReport | null
  aging: AgingReport | null
  reconciliation: ReconciliationReport | null
  feeTrend: FeeCollectionPoint[]
  payrollPending?: number
}>()

const currency = computed(() => String(props.summary?.currency ?? 'USD'))

const invoiceStatusRows = computed(() => {
  const byStatus = props.financial?.by_status ?? {}
  const total = Object.values(byStatus).reduce((sum, n) => sum + Number(n ?? 0), 0) || 1
  return Object.entries(byStatus)
    .map(([status, count]) => ({
      status,
      label: INVOICE_STATUS_LABELS[status] ?? status,
      count: Number(count ?? 0),
      share: Math.round((Number(count ?? 0) / total) * 100),
    }))
    .sort((a, b) => b.count - a.count)
})

const agingBuckets = computed(() => {
  const buckets = props.aging?.buckets ?? {}
  return Object.entries(buckets).map(([key, bucket]) => ({
    key,
    label: bucket.label ?? key,
    total: Number(bucket.total ?? 0),
    count: Number(bucket.count ?? 0),
  }))
})

const agingMax = computed(() =>
  Math.max(...agingBuckets.value.map((b) => b.total), 1),
)

const overdueInvoices = computed(() =>
  [...(props.aging?.invoices ?? [])]
    .filter((row) => Number(row.balance ?? 0) > 0)
    .sort((a, b) => Number(b.days_past_due ?? 0) - Number(a.days_past_due ?? 0))
    .slice(0, 8),
)

const feeTrendRows = computed(() =>
  [...props.feeTrend]
    .sort((a, b) => String(a.month ?? '').localeCompare(String(b.month ?? '')))
    .slice(-6),
)

const feeTrendMax = computed(() =>
  Math.max(...feeTrendRows.value.map((row) => Number(row.total ?? 0)), 1),
)

const paymentMethods = computed(() =>
  [...(props.reconciliation?.by_payment_method ?? [])]
    .sort((a, b) => Number(b.total ?? 0) - Number(a.total ?? 0)),
)

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  if (status === 'overdue') return 'destructive'
  if (status === 'paid') return 'default'
  if (status === 'partial') return 'secondary'
  return 'outline'
}
</script>

<template>
  <div class="space-y-6">
    <PageLoader v-if="loading" label="Refreshing finance analytics" />

    <template v-else>
      <Card v-if="summary">
        <CardHeader class="border-b border-border/60 pb-4">
          <CardTitle class="text-base">School finance snapshot</CardTitle>
          <CardDescription>
            Live totals from invoices and completed payments ({{ currency }}).
          </CardDescription>
        </CardHeader>
        <CardContent class="pt-6">
          <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <dt class="text-sm text-muted-foreground">Outstanding</dt>
              <dd class="text-lg font-semibold text-destructive">
                {{ formatMoney(summary.totalOutstanding, currency) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Collected today</dt>
              <dd class="text-lg font-semibold">
                {{ formatMoney(summary.collectedToday, currency) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Total revenue</dt>
              <dd class="text-lg font-semibold">
                {{ formatMoney(summary.totalRevenue, currency) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Invoices on file</dt>
              <dd class="text-lg font-semibold">
                {{ summary.totalInvoices ?? 0 }}
                <span class="text-sm font-normal text-muted-foreground">
                  · {{ summary.overdueInvoices ?? 0 }} overdue
                </span>
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <CardTitle class="flex items-center gap-2 text-base">
              <Receipt class="h-4 w-4 text-primary" aria-hidden="true" />
              Invoice status
            </CardTitle>
            <CardDescription>Breakdown of all invoices in the register.</CardDescription>
          </CardHeader>
          <CardContent class="pt-6">
            <EmptyState
              v-if="!invoiceStatusRows.length"
              title="No invoice data"
              description="Create fee invoices to see status breakdown here."
            />
            <ul v-else class="space-y-4" role="list">
              <li v-for="row in invoiceStatusRows" :key="row.status" class="space-y-2">
                <div class="flex items-center justify-between gap-2 text-sm">
                  <span class="font-medium">{{ row.label }}</span>
                  <span class="text-muted-foreground">{{ row.count }} · {{ row.share }}%</span>
                </div>
                <Progress :model-value="row.share" class="h-2" />
              </li>
            </ul>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <CardTitle class="flex items-center gap-2 text-base">
              <TrendingUp class="h-4 w-4 text-primary" aria-hidden="true" />
              Collections (6 months)
            </CardTitle>
            <CardDescription>Completed payments grouped by month.</CardDescription>
          </CardHeader>
          <CardContent class="pt-6">
            <EmptyState
              v-if="!feeTrendRows.length"
              title="No collection history"
              description="Recorded payments will appear in this trend."
            />
            <ul v-else class="space-y-4" role="list">
              <li v-for="row in feeTrendRows" :key="row.month" class="space-y-2">
                <div class="flex items-center justify-between gap-2 text-sm">
                  <span class="font-medium">{{ row.month }}</span>
                  <span>{{ formatMoney(row.total, currency) }}</span>
                </div>
                <Progress
                  :model-value="Math.round((Number(row.total ?? 0) / feeTrendMax) * 100)"
                  class="h-2"
                />
              </li>
            </ul>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <CardTitle class="flex items-center gap-2 text-base">
              <Wallet class="h-4 w-4 text-primary" aria-hidden="true" />
              Aging report
            </CardTitle>
            <CardDescription>
              Outstanding balances by days past due
              <span v-if="aging?.total_outstanding != null">
                · {{ formatMoney(aging.total_outstanding, currency) }} total
              </span>
            </CardDescription>
          </CardHeader>
          <CardContent class="pt-6">
            <EmptyState
              v-if="!agingBuckets.length"
              title="No aging data"
              description="Outstanding invoices will populate aging buckets."
            />
            <ul v-else class="space-y-4" role="list">
              <li v-for="bucket in agingBuckets" :key="bucket.key" class="space-y-2">
                <div class="flex items-center justify-between gap-2 text-sm">
                  <span class="font-medium">{{ bucket.label }}</span>
                  <span class="text-muted-foreground">
                    {{ bucket.count }} · {{ formatMoney(bucket.total, currency) }}
                  </span>
                </div>
                <Progress
                  :model-value="Math.round((bucket.total / agingMax) * 100)"
                  class="h-2"
                />
              </li>
            </ul>
            <Button variant="outline" size="sm" class="mt-4" as-child>
              <RouterLink to="/finance/reports">
                Full aging report
                <ArrowRight class="ml-1 h-3.5 w-3.5" aria-hidden="true" />
              </RouterLink>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="border-b border-border/60 pb-4">
            <CardTitle class="flex items-center gap-2 text-base">
              <Banknote class="h-4 w-4 text-primary" aria-hidden="true" />
              This month
            </CardTitle>
            <CardDescription>
              Reconciliation for
              {{ reconciliation?.from ?? 'current period' }}
              <span v-if="reconciliation?.to">– {{ reconciliation.to }}</span>
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4 pt-6">
            <dl class="grid gap-3 sm:grid-cols-2">
              <div>
                <dt class="text-sm text-muted-foreground">Invoiced</dt>
                <dd class="font-semibold">{{ formatMoney(reconciliation?.invoiced, currency) }}</dd>
              </div>
              <div>
                <dt class="text-sm text-muted-foreground">Collected</dt>
                <dd class="font-semibold">{{ formatMoney(reconciliation?.collected, currency) }}</dd>
              </div>
              <div>
                <dt class="text-sm text-muted-foreground">Net collected</dt>
                <dd class="font-semibold">{{ formatMoney(reconciliation?.net_collected, currency) }}</dd>
              </div>
              <div>
                <dt class="text-sm text-muted-foreground">Payroll pending</dt>
                <dd class="font-semibold">{{ formatMoney(payrollPending ?? 0, currency) }}</dd>
              </div>
            </dl>

            <div v-if="paymentMethods.length" class="space-y-2">
              <p class="text-sm font-medium">By payment method</p>
              <ul class="space-y-2" role="list">
                <li
                  v-for="method in paymentMethods"
                  :key="String(method.method)"
                  class="flex items-center justify-between text-sm"
                >
                  <span>{{ formatPaymentMethod(method.method) }}</span>
                  <span class="text-muted-foreground">
                    {{ method.count ?? 0 }} · {{ formatMoney(method.total, currency) }}
                  </span>
                </li>
              </ul>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="border-b border-border/60 pb-4">
          <CardTitle class="text-base">Highest-risk balances</CardTitle>
          <CardDescription>Outstanding invoices sorted by days past due.</CardDescription>
        </CardHeader>
        <CardContent class="pt-6">
          <EmptyState
            v-if="!overdueInvoices.length"
            title="No outstanding balances"
            description="All invoices are paid or current."
          />
          <div v-else class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Invoice</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead class="text-right">Days overdue</TableHead>
                  <TableHead class="text-right">Balance</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in overdueInvoices" :key="row.invoice_number">
                  <TableCell class="font-medium">{{ row.invoice_number ?? '—' }}</TableCell>
                  <TableCell>{{ row.student_name ?? '—' }}</TableCell>
                  <TableCell>
                    <Badge :variant="statusVariant(String(row.status ?? ''))">
                      {{ INVOICE_STATUS_LABELS[String(row.status ?? '')] ?? row.status ?? '—' }}
                    </Badge>
                  </TableCell>
                  <TableCell class="text-right">{{ row.days_past_due ?? 0 }}</TableCell>
                  <TableCell class="text-right">
                    {{ formatMoney(row.balance, String(row.currency ?? currency)) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
          <Button variant="outline" size="sm" class="mt-4" as-child>
            <RouterLink to="/finance/invoices">
              Manage invoices
              <ArrowRight class="ml-1 h-3.5 w-3.5" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
