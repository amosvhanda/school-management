<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { financeApi } from '@/services/api.service'
import { formatMoney } from '@/lib/finance-constants'
import { formatDate } from '@/lib/format'

const loading = ref(true)
const error = ref<string | null>(null)
const report = ref<Record<string, unknown> | null>(null)

const buckets = computed(() => {
  const raw = report.value?.buckets as Record<string, { label: string; total: number; count: number }> | undefined
  if (!raw) return []
  return Object.entries(raw).map(([key, value]) => ({ key, ...value }))
})

const invoices = computed(() => {
  return (report.value?.invoices as Array<Record<string, unknown>> | undefined) ?? []
})

const currency = computed(() => String(invoices.value[0]?.currency ?? 'USD'))

async function load() {
  loading.value = true
  error.value = null
  try {
    report.value = await financeApi.aging()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load aging report'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Accounts receivable aging</h1>
        <p class="text-muted-foreground">
          Outstanding invoice balances grouped by how long they have been due
        </p>
      </div>
      <Button variant="outline" as-child>
        <RouterLink to="/finance">Back to overview</RouterLink>
      </Button>
    </div>

    <PageLoader v-if="loading" label="Loading aging report" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="report">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <Card v-for="bucket in buckets" :key="bucket.key">
          <CardHeader class="pb-2">
            <CardDescription>{{ bucket.label }}</CardDescription>
            <CardTitle class="text-xl tabular-nums">
              {{ formatMoney(bucket.total, currency) }}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-muted-foreground">{{ bucket.count }} invoice(s)</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Outstanding invoices</CardTitle>
          <CardDescription>
            Total outstanding: {{ formatMoney(report.total_outstanding, currency) }}
          </CardDescription>
        </CardHeader>
        <CardContent class="overflow-x-auto p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Invoice</TableHead>
                <TableHead>Student</TableHead>
                <TableHead>Due date</TableHead>
                <TableHead>Days past due</TableHead>
                <TableHead class="text-right">Balance</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in invoices.slice(0, 100)" :key="String(row.invoice_id)">
                <TableCell class="font-mono text-xs">{{ row.invoice_number }}</TableCell>
                <TableCell>{{ row.student_name }}</TableCell>
                <TableCell>{{ formatDate(row.due_date) }}</TableCell>
                <TableCell>{{ row.days_past_due }}</TableCell>
                <TableCell class="text-right tabular-nums">
                  {{ formatMoney(row.balance, String(row.currency ?? currency)) }}
                </TableCell>
                <TableCell class="capitalize">{{ row.status }}</TableCell>
              </TableRow>
              <TableRow v-if="!invoices.length">
                <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                  No outstanding invoices
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-if="invoices.length > 100" class="border-t p-4 text-sm text-muted-foreground">
            Showing first 100 of {{ invoices.length }} invoices. Use invoice filters for detailed search.
          </p>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
