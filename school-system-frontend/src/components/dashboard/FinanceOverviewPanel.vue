<script setup lang="ts">
import { computed } from 'vue'
import { Banknote, Receipt, TrendingDown, Wallet, ArrowDownCircle, Scale } from 'lucide-vue-next'
import KpiCard from './KpiCard.vue'
import { formatMoney } from '@/lib/finance-constants'

const props = defineProps<{
  summary: Record<string, unknown>
  kpis: {
    outstandingFees: number
    paymentsToday: number
    totalRevenue: number
    paymentsGrowth: number
  }
}>()

const currency = computed(() => String(props.summary.currency ?? 'USD'))
const payrollPaidToday = computed(() => Number(props.summary.payrollPaidToday ?? 0))
const payrollPaidThisMonth = computed(() => Number(props.summary.payrollPaidThisMonth ?? 0))
const payrollPending = computed(() => Number(props.summary.payrollPending ?? 0))
const netCashToday = computed(() => Number(props.summary.netCashToday ?? (props.kpis.paymentsToday - payrollPaidToday.value)))
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <KpiCard
        title="Outstanding fees"
        :value="formatMoney(kpis.outstandingFees, currency)"
        subtitle="Unpaid invoice balances"
        :icon="TrendingDown"
        accent="danger"
        :trend="kpis.paymentsGrowth"
        href="/finance/invoices"
      />
      <KpiCard
        title="Collected today"
        :value="formatMoney(kpis.paymentsToday, currency)"
        subtitle="Money in (fees)"
        :icon="Wallet"
        accent="success"
        href="/finance/payments"
      />
      <KpiCard
        title="Payroll paid today"
        :value="formatMoney(payrollPaidToday, currency)"
        subtitle="Money out (staff)"
        :icon="ArrowDownCircle"
        accent="warning"
        href="/finance/transactions?category=payroll&type=expense"
      />
      <KpiCard
        title="Net cash today"
        :value="formatMoney(netCashToday, currency)"
        subtitle="Collections − payroll"
        :icon="Scale"
        href="/finance/cash-flow"
      />
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <KpiCard
        title="Total revenue"
        :value="formatMoney(kpis.totalRevenue, currency)"
        subtitle="All time fee collections"
        :icon="Banknote"
        href="/finance/payments"
      />
      <KpiCard
        title="Payroll this month"
        :value="formatMoney(payrollPaidThisMonth, currency)"
        subtitle="Staff payments posted to ledger"
        :icon="ArrowDownCircle"
        href="/finance/payroll"
      />
      <KpiCard
        title="Payroll still due"
        :value="formatMoney(payrollPending, currency)"
        :subtitle="`${Number(summary.pendingInvoices ?? 0).toLocaleString()} invoices pending`"
        :icon="Receipt"
        accent="warning"
        href="/finance/payroll?status=pending"
      />
    </div>
  </div>
</template>
