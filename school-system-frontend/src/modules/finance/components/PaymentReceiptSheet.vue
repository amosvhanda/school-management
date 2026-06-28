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
import { formatMoney, formatPaymentMethod } from '@/lib/finance-constants'

const props = defineProps<{
  open: boolean
  receipt: Record<string, unknown> | null
  loading?: boolean
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

const payment = computed(() => {
  const raw = props.receipt?.payment as Record<string, unknown> | undefined
  return raw ?? null
})

const school = computed(() => props.receipt?.school as Record<string, unknown> | undefined)
const student = computed(() => payment.value?.student as Record<string, unknown> | undefined)
const invoice = computed(() => payment.value?.invoice as Record<string, unknown> | undefined)

function printReceipt() {
  window.print()
}
</script>

<template>
  <Sheet :open="open" @update:open="$emit('update:open', $event)">
    <SheetContent class="w-full overflow-y-auto sm:max-w-md print:max-w-none" side="right">
      <SheetHeader class="print:hidden">
        <SheetTitle>Payment receipt</SheetTitle>
        <SheetDescription>Official record of payment received</SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="py-8 text-sm text-muted-foreground">Loading receipt…</div>

      <article
        v-else-if="payment"
        class="mt-6 space-y-6 rounded-lg border bg-card p-6 print:border-0 print:p-0"
        aria-label="Payment receipt"
      >
        <header class="space-y-1 border-b pb-4 text-center">
          <p class="text-xs font-semibold uppercase tracking-widest text-primary">Official receipt</p>
          <h2 class="text-lg font-semibold">{{ school?.name ?? 'School' }}</h2>
          <p v-if="school?.address" class="text-xs text-muted-foreground">{{ school.address }}</p>
          <p class="font-mono text-sm">{{ receipt?.receipt_number }}</p>
        </header>

        <dl class="grid gap-3 text-sm">
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Date</dt>
            <dd>{{ payment.date ?? '—' }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Student</dt>
            <dd class="text-right">{{ student?.full_name ?? '—' }}</dd>
          </div>
          <div v-if="student?.student_number" class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Student no.</dt>
            <dd>{{ student.student_number }}</dd>
          </div>
          <div v-if="invoice?.invoice_number" class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Invoice</dt>
            <dd>{{ invoice.invoice_number }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Method</dt>
            <dd>{{ formatPaymentMethod(payment.method) }}</dd>
          </div>
          <div v-if="payment.reference" class="flex justify-between gap-4">
            <dt class="text-muted-foreground">Reference</dt>
            <dd class="font-mono text-xs">{{ payment.reference }}</dd>
          </div>
        </dl>

        <div class="rounded-lg bg-muted/50 p-4 text-center">
          <p class="text-xs uppercase tracking-wide text-muted-foreground">Amount received</p>
          <p class="text-2xl font-bold tabular-nums">
            {{ formatMoney(payment.amount, String(payment.currency ?? 'USD')) }}
          </p>
          <p v-if="invoice?.balance != null" class="mt-1 text-xs text-muted-foreground">
            Invoice balance after payment: {{ formatMoney(invoice.balance, String(payment.currency ?? 'USD')) }}
          </p>
        </div>

        <p class="text-center text-xs text-muted-foreground">
          Thank you for your payment. Keep this receipt for your records.
        </p>

        <div class="flex justify-end gap-2 print:hidden">
          <Button variant="outline" @click="printReceipt">
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
  article[aria-label='Payment receipt'],
  article[aria-label='Payment receipt'] * {
    visibility: visible;
  }
  article[aria-label='Payment receipt'] {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
