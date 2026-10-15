<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Banknote, CalendarDays, CheckCircle2, Clock3, Printer, Wallet } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { badgeVariantForStatus, STATUS_TONE_ICON } from '@/lib/ui-status'
import { formatDate } from '@/lib/format'
import { cn } from '@/lib/utils'

export type ProfileInvoice = {
  id: number
  invoice_number?: string
  amount?: number
  total?: number
  balance?: number
  currency?: string
  status?: string
  due_date?: string
  description?: string
}

const props = defineProps<{
  invoices: ProfileInvoice[]
  currency?: string
  canManage?: boolean
  initiatingPaymentId?: number | null
}>()

const emit = defineEmits<{
  create: []
  pay: [inv: ProfileInvoice]
  record: [inv: ProfileInvoice]
  print: [invoiceId: number]
}>()

function money(amount?: number, currency = props.currency ?? 'USD') {
  return `${currency} ${Number(amount ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`
}

function invoiceTotal(inv: ProfileInvoice) {
  return Number(inv.amount ?? inv.total ?? 0)
}

const totals = computed(() => {
  let total = 0
  let paid = 0
  let pending = 0
  let overdue = 0
  for (const inv of props.invoices) {
    const t = invoiceTotal(inv)
    const bal = Number(inv.balance ?? 0)
    total += t
    paid += Math.max(0, t - bal)
    const status = String(inv.status ?? '').toLowerCase()
    if (status === 'overdue') overdue += bal
    else if (bal > 0) pending += bal
  }
  return { total, paid, pending, overdue }
})

const collectedPct = computed(() => {
  if (totals.value.total <= 0) return 0
  return Math.round((totals.value.paid / totals.value.total) * 100)
})

const nextDue = computed(() => {
  const open = props.invoices
    .filter((inv) => Number(inv.balance ?? 0) > 0)
    .sort((a, b) => String(a.due_date ?? '').localeCompare(String(b.due_date ?? '')))
  return open[0] ?? null
})

function canRecord(inv: ProfileInvoice) {
  const status = String(inv.status ?? '').toLowerCase()
  const balance = Number(inv.balance ?? 0)
  return ['pending', 'partial', 'overdue'].includes(status) && balance > 0
}
</script>

<template>
  <div class="space-y-6">
    <Card v-if="nextDue" class="border-primary/20 bg-primary/5">
      <CardContent class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
          <div :class="cn('flex size-11 shrink-0 items-center justify-center rounded-2xl', STATUS_TONE_ICON.accent)">
            <CalendarDays class="size-5" aria-hidden="true" />
          </div>
          <div class="space-y-1">
            <p class="text-sm font-semibold text-foreground">Next payment due</p>
            <p class="text-sm text-muted-foreground">
              {{ nextDue.description || nextDue.invoice_number || 'Invoice' }}
              ·
              <span class="font-semibold text-foreground">{{ money(nextDue.balance, nextDue.currency) }}</span>
              · due {{ formatDate(nextDue.due_date) }}
            </p>
          </div>
        </div>
        <div v-if="canManage" class="flex flex-wrap gap-2">
          <Button type="button" class="rounded-xl" @click="emit('pay', nextDue)">
            <Banknote class="mr-1.5 size-4" aria-hidden="true" />
            Pay now
          </Button>
          <Button type="button" variant="outline" class="rounded-xl" @click="emit('record', nextDue)">
            Collect fees
          </Button>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-4 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)]">
      <Card class="flex flex-col items-center justify-center gap-3 p-6">
        <div
          class="relative flex size-36 items-center justify-center rounded-full"
          :style="{
            background: `conic-gradient(var(--chart-2) ${collectedPct}%, var(--muted) 0)`,
          }"
          role="img"
          :aria-label="`${collectedPct}% collected`"
        >
          <div class="flex size-[7.25rem] flex-col items-center justify-center rounded-full bg-card text-center">
            <p class="text-2xl font-semibold tabular-nums">{{ collectedPct }}%</p>
            <p class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Collected</p>
          </div>
        </div>
        <div class="flex flex-wrap justify-center gap-3 text-xs text-muted-foreground">
          <span class="inline-flex items-center gap-1.5">
            <span class="size-2 rounded-full bg-chart-2" aria-hidden="true" />
            Paid {{ money(totals.paid) }}
          </span>
          <span class="inline-flex items-center gap-1.5">
            <span class="size-2 rounded-full bg-chart-3" aria-hidden="true" />
            Pending {{ money(totals.pending) }}
          </span>
          <span class="inline-flex items-center gap-1.5">
            <span class="size-2 rounded-full bg-destructive" aria-hidden="true" />
            Overdue {{ money(totals.overdue) }}
          </span>
        </div>
      </Card>

      <div class="grid gap-3 sm:grid-cols-2">
        <Card class="border-l-4 border-l-primary">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.accent)">
              <Wallet class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-xl font-semibold tabular-nums">{{ money(totals.total) }}</p>
              <p class="text-xs text-muted-foreground">Total fees</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-chart-2">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.success)">
              <CheckCircle2 class="size-5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xl font-semibold tabular-nums">{{ money(totals.paid) }}</p>
              <p class="text-xs text-muted-foreground">Paid</p>
              <Progress :model-value="collectedPct" class="mt-2 h-1.5" />
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-chart-3">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.warning)">
              <Clock3 class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-xl font-semibold tabular-nums">{{ money(totals.pending) }}</p>
              <p class="text-xs text-muted-foreground">Pending</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-l-4 border-l-destructive">
          <CardContent class="flex items-center gap-3 p-4">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.danger)">
              <AlertTriangle class="size-5" aria-hidden="true" />
            </div>
            <div>
              <p class="text-xl font-semibold tabular-nums">{{ money(totals.overdue) }}</p>
              <p class="text-xs text-muted-foreground">Overdue</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <Card>
      <CardHeader class="flex flex-row items-center justify-between space-y-0 border-b border-border/50 px-5 py-4">
        <div>
          <CardTitle class="text-base">Invoices</CardTitle>
          <CardDescription>Fee invoices and outstanding balances</CardDescription>
        </div>
        <Button v-if="canManage" type="button" size="sm" class="rounded-xl" @click="emit('create')">
          New invoice
        </Button>
      </CardHeader>
      <CardContent class="space-y-3 p-5">
        <template v-if="invoices.length">
          <article
            v-for="inv in invoices"
            :key="inv.id"
            class="flex flex-col gap-3 rounded-2xl border border-border/70 p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex min-w-0 items-start gap-3">
              <div
                :class="cn(
                  'flex size-10 shrink-0 items-center justify-center rounded-xl',
                  String(inv.status).toLowerCase() === 'paid'
                    ? STATUS_TONE_ICON.success
                    : String(inv.status).toLowerCase() === 'overdue'
                      ? STATUS_TONE_ICON.danger
                      : STATUS_TONE_ICON.warning,
                )"
              >
                <Banknote class="size-4" aria-hidden="true" />
              </div>
              <div class="min-w-0 space-y-1">
                <p class="truncate text-sm font-semibold">
                  {{ inv.description || inv.invoice_number || `Invoice #${inv.id}` }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ inv.invoice_number ?? `#${inv.id}` }}
                  · {{ formatDate(inv.due_date) }}
                </p>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
              <p class="text-sm font-semibold tabular-nums">{{ money(invoiceTotal(inv), inv.currency) }}</p>
              <Badge :variant="badgeVariantForStatus(inv.status)" class="capitalize">
                {{ inv.status ?? '—' }}
              </Badge>
              <Button
                v-if="canManage && canRecord(inv)"
                type="button"
                size="sm"
                class="rounded-xl"
                :disabled="initiatingPaymentId === inv.id"
                @click="emit('pay', inv)"
              >
                Pay
              </Button>
              <Button
                v-if="canManage"
                type="button"
                variant="outline"
                size="icon-sm"
                class="rounded-xl"
                :aria-label="`Print invoice ${inv.invoice_number ?? inv.id}`"
                @click="emit('print', inv.id)"
              >
                <Printer class="size-4" />
              </Button>
            </div>
          </article>
        </template>
        <EmptyState
          v-else
          variant="embedded"
          title="No invoices"
          description="Fee invoices for this student will appear here."
        />
      </CardContent>
    </Card>
  </div>
</template>
