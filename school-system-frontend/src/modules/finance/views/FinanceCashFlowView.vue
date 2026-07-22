<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowDownCircle, ArrowUpCircle, CheckCircle2, Scale, AlertTriangle } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { financeApi } from '@/services/api.service'
import { formatMoney, formatPaymentMethod } from '@/lib/finance-constants'

type Period = 'daily' | 'weekly' | 'monthly' | 'ytd'

const periods: Array<{ value: Period; label: string }> = [
  { value: 'daily', label: 'Today' },
  { value: 'weekly', label: 'This week' },
  { value: 'monthly', label: 'This month' },
  { value: 'ytd', label: 'Year to date' },
]

const period = ref<Period>('monthly')
const loading = ref(true)
const error = ref<string | null>(null)
const report = ref<Record<string, unknown> | null>(null)

const currency = computed(() => String(report.value?.currency ?? 'USD'))
const moneyIn = computed(() => (report.value?.money_in as Record<string, unknown> | undefined) ?? {})
const moneyOut = computed(() => (report.value?.money_out as Record<string, unknown> | undefined) ?? {})
const books = computed(() => (report.value?.books as Record<string, unknown> | undefined) ?? {})
const balanceCheck = computed(() => (report.value?.balance_check as Record<string, unknown> | undefined) ?? {})
const isBalanced = computed(() => balanceCheck.value.is_balanced === true)

const inSources = computed(() => {
  return (moneyIn.value.by_source as Array<Record<string, unknown>> | undefined) ?? []
})
const outSources = computed(() => {
  return (moneyOut.value.by_source as Array<Record<string, unknown>> | undefined) ?? []
})
const inMethods = computed(() => {
  return (moneyIn.value.by_method as Array<Record<string, unknown>> | undefined) ?? []
})
const outMethods = computed(() => {
  return (moneyOut.value.by_method as Array<Record<string, unknown>> | undefined) ?? []
})

async function load() {
  loading.value = true
  error.value = null
  try {
    report.value = await financeApi.cashFlow({ period: period.value })
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load cash flow'
    report.value = null
  } finally {
    loading.value = false
  }
}

watch(period, () => {
  void load()
})

onMounted(load)
</script>

<template>
  <PageShell
    title="Cash flow 360"
    :description="`Money coming in and going out — they always reconcile: ${report?.equation ?? 'Money in − Money out = Net cash'}`"
    max-width="wide"
  >
    <template #actions>
      <div class="flex flex-wrap gap-1" role="group" aria-label="Report period">
        <Button
          v-for="option in periods"
          :key="option.value"
          size="sm"
          :variant="period === option.value ? 'default' : 'outline'"
          @click="period = option.value"
        >
          {{ option.label }}
        </Button>
      </div>
      <Button variant="outline" as-child>
        <RouterLink to="/finance">Overview</RouterLink>
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading cash flow" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="report">
      <p class="text-sm text-muted-foreground">
        Period {{ report.from }} – {{ report.to }} · {{ currency }}
      </p>

      <div class="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription class="flex items-center gap-2">
              <ArrowUpCircle class="h-4 w-4 text-emerald-600" aria-hidden="true" />
              Money in
            </CardDescription>
            <CardTitle class="text-2xl tabular-nums text-emerald-700 dark:text-emerald-400">
              {{ formatMoney(moneyIn.total, currency) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Button variant="link" class="h-auto px-0" as-child>
              <RouterLink to="/finance/transactions?type=payment">View collections ledger</RouterLink>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription class="flex items-center gap-2">
              <ArrowDownCircle class="h-4 w-4 text-amber-600" aria-hidden="true" />
              Money out
            </CardDescription>
            <CardTitle class="text-2xl tabular-nums text-amber-700 dark:text-amber-400">
              {{ formatMoney(moneyOut.total, currency) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <Button variant="link" class="h-auto px-0" as-child>
              <RouterLink to="/finance/transactions?type=expense">View expense ledger</RouterLink>
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardDescription class="flex items-center gap-2">
              <Scale class="h-4 w-4 text-primary" aria-hidden="true" />
              Net cash
            </CardDescription>
            <CardTitle class="text-2xl tabular-nums">
              {{ formatMoney(report.net_cash, currency) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-muted-foreground">
              {{ formatMoney(moneyIn.total, currency) }}
              −
              {{ formatMoney(moneyOut.total, currency) }}
            </p>
          </CardContent>
        </Card>
      </div>

      <Card
        :class="isBalanced
          ? 'border-emerald-500/40 bg-emerald-500/5'
          : 'border-amber-500/40 bg-amber-500/5'"
      >
        <CardHeader>
          <div class="flex flex-wrap items-center gap-2">
            <CardTitle class="text-base">Balance check</CardTitle>
            <Badge :variant="isBalanced ? 'default' : 'secondary'">
              <component
                :is="isBalanced ? CheckCircle2 : AlertTriangle"
                class="mr-1 h-3.5 w-3.5"
                aria-hidden="true"
              />
              {{ isBalanced ? 'Balanced' : 'Variance' }}
            </Badge>
          </div>
          <CardDescription>{{ balanceCheck.message }}</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <dt class="text-sm text-muted-foreground">Source money in</dt>
              <dd class="font-semibold tabular-nums">{{ formatMoney(balanceCheck.source_money_in, currency) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Ledger money in</dt>
              <dd class="font-semibold tabular-nums">{{ formatMoney(balanceCheck.ledger_money_in, currency) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Source money out</dt>
              <dd class="font-semibold tabular-nums">{{ formatMoney(balanceCheck.source_money_out, currency) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">In variance</dt>
              <dd class="font-semibold tabular-nums">{{ formatMoney(balanceCheck.in_variance, currency) }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Money in by source</CardTitle>
            <CardDescription>Where cash entered the school</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Source</TableHead>
                  <TableHead class="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in inSources" :key="String(row.source)">
                  <TableCell>{{ row.label }}</TableCell>
                  <TableCell class="text-right tabular-nums">{{ formatMoney(row.total, currency) }}</TableCell>
                </TableRow>
                <TableRow>
                  <TableCell class="font-medium">Total in</TableCell>
                  <TableCell class="text-right font-medium tabular-nums">
                    {{ formatMoney(moneyIn.total, currency) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Money out by source</CardTitle>
            <CardDescription>Where cash left the school</CardDescription>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Source</TableHead>
                  <TableHead class="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in outSources" :key="String(row.source)">
                  <TableCell>{{ row.label }}</TableCell>
                  <TableCell class="text-right tabular-nums">{{ formatMoney(row.total, currency) }}</TableCell>
                </TableRow>
                <TableRow>
                  <TableCell class="font-medium">Total out</TableCell>
                  <TableCell class="text-right font-medium tabular-nums">
                    {{ formatMoney(moneyOut.total, currency) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Money in by method</CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Method</TableHead>
                  <TableHead>Count</TableHead>
                  <TableHead class="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in inMethods" :key="String(row.method)">
                  <TableCell>{{ formatPaymentMethod(row.method) }}</TableCell>
                  <TableCell>{{ row.count }}</TableCell>
                  <TableCell class="text-right tabular-nums">{{ formatMoney(row.total, currency) }}</TableCell>
                </TableRow>
                <TableRow v-if="!inMethods.length">
                  <TableCell colspan="3" class="h-20 text-center text-muted-foreground">No inflows this period</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Money out by method</CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Method</TableHead>
                  <TableHead>Count</TableHead>
                  <TableHead class="text-right">Amount</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in outMethods" :key="String(row.method)">
                  <TableCell>{{ formatPaymentMethod(row.method) }}</TableCell>
                  <TableCell>{{ row.count }}</TableCell>
                  <TableCell class="text-right tabular-nums">{{ formatMoney(row.total, currency) }}</TableCell>
                </TableRow>
                <TableRow v-if="!outMethods.length">
                  <TableCell colspan="3" class="h-20 text-center text-muted-foreground">No outflows this period</TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Still on the books (not cash yet)</CardTitle>
          <CardDescription>{{ books.note }}</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-4 sm:grid-cols-3">
            <div>
              <dt class="text-sm text-muted-foreground">Fees invoiced (period)</dt>
              <dd class="text-lg font-semibold tabular-nums">{{ formatMoney(books.fees_invoiced, currency) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Fees outstanding</dt>
              <dd class="text-lg font-semibold tabular-nums">{{ formatMoney(books.fees_outstanding, currency) }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Payroll still due</dt>
              <dd class="text-lg font-semibold tabular-nums">{{ formatMoney(books.payroll_pending, currency) }}</dd>
            </div>
          </dl>
          <div class="mt-4 flex flex-wrap gap-2">
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/finance/reports">Aging report</RouterLink>
            </Button>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/finance/payroll?status=pending">Pending payroll</RouterLink>
            </Button>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/finance/payments">Fee payments</RouterLink>
            </Button>
          </div>
        </CardContent>
      </Card>
    </template>
  </PageShell>
</template>
