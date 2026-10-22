<script setup lang="ts">
import { computed } from 'vue'
import { Printer } from '@lucide/vue'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { formatMoney } from '@/lib/finance-constants'
import { formatDate } from '@/lib/format'

const props = defineProps<{
  open: boolean
  document: Record<string, unknown> | null
  loading?: boolean
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

const invoice = computed(() => {
  const raw = props.document?.invoice as Record<string, unknown> | undefined
  return raw ?? null
})

const school = computed(() => props.document?.school as Record<string, unknown> | undefined)
const student = computed(() => invoice.value?.student as Record<string, unknown> | undefined)
const payments = computed(() => {
  const rows = invoice.value?.payments
  return Array.isArray(rows) ? (rows as Record<string, unknown>[]) : []
})

function printInvoice() {
  window.print()
}
</script>

<template>
  <Sheet :open="open" @update:open="$emit('update:open', $event)">
    <SheetContent class="w-full overflow-y-auto sm:max-w-md print:max-w-none" side="right">
      <SheetHeader class="print:hidden">
        <SheetTitle>Invoice</SheetTitle>
        <SheetDescription>Printable fee invoice for the student</SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="py-8 text-sm text-muted-foreground">Loading invoice…</div>

      <article
        v-else-if="invoice"
        class="mt-6 space-y-6 rounded-lg border bg-card p-6 print:border-0 print:p-0"
        aria-label="Fee invoice"
      >
        <header class="space-y-1 border-b pb-4 text-center">
          <p class="text-xs font-semibold uppercase tracking-widest text-primary">Fee invoice</p>
          <h2 class="text-lg font-semibold">{{ school?.name ?? 'School' }}</h2>
          <p v-if="school?.address" class="text-xs text-muted-foreground">{{ school.address }}</p>
          <p class="font-mono text-sm">{{ invoice.invoice_number || document?.document_number }}</p>
        </header>

        <dl class="grid gap-3 text-sm">
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Due date</dt>
            <dd>{{ formatDate(invoice.due_date) }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Student</dt>
            <dd class="text-right">{{ student?.full_name ?? '—' }}</dd>
          </div>
          <div v-if="student?.student_number" class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Student no.</dt>
            <dd>{{ student.student_number }}</dd>
          </div>
          <div v-if="invoice.description" class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Description</dt>
            <dd class="text-right">{{ invoice.description }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Status</dt>
            <dd class="capitalize">{{ invoice.status ?? '—' }}</dd>
          </div>
        </dl>

        <div class="space-y-2 rounded-lg bg-muted/50 p-4 text-sm">
          <div class="flex justify-between gap-4">
            <span class="text-muted-foreground">Amount</span>
            <span class="tabular-nums font-medium">
              {{ formatMoney(invoice.amount, String(invoice.currency ?? 'USD')) }}
            </span>
          </div>
          <div v-if="invoice.discount_amount" class="flex justify-between gap-4">
            <span class="text-muted-foreground">Discount</span>
            <span class="tabular-nums">
              {{ formatMoney(invoice.discount_amount, String(invoice.currency ?? 'USD')) }}
            </span>
          </div>
          <div class="flex justify-between gap-4">
            <span class="text-muted-foreground">Paid</span>
            <span class="tabular-nums">
              {{ formatMoney(invoice.amount_paid, String(invoice.currency ?? 'USD')) }}
            </span>
          </div>
          <div class="flex justify-between gap-4 border-t border-border/60 pt-2">
            <span class="font-medium">Balance due</span>
            <span class="text-lg font-bold tabular-nums">
              {{ formatMoney(invoice.balance, String(invoice.currency ?? 'USD')) }}
            </span>
          </div>
        </div>

        <div v-if="payments.length" class="space-y-2">
          <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Payments</p>
          <ul class="space-y-1 text-sm">
            <li
              v-for="payment in payments"
              :key="String(payment.id)"
              class="flex justify-between gap-3"
            >
              <span class="text-muted-foreground">
                {{ formatDate(payment.date) }} · {{ payment.method || 'payment' }}
              </span>
              <span class="tabular-nums">
                {{ formatMoney(payment.amount, String(invoice.currency ?? 'USD')) }}
              </span>
            </li>
          </ul>
        </div>

        <p class="text-center text-xs text-muted-foreground">
          Please settle outstanding balances by the due date.
        </p>

        <div class="flex justify-end gap-2 print:hidden">
          <Button variant="outline" @click="printInvoice">
            <Printer class="mr-2 h-4 w-4" aria-hidden="true" />
            Print
          </Button>
        </div>
      </article>
    </SheetContent>
  </Sheet>
</template>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  article[aria-label='Fee invoice'],
  article[aria-label='Fee invoice'] * {
    visibility: visible;
  }
  article[aria-label='Fee invoice'] {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
