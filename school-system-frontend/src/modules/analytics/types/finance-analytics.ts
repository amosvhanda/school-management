import type { PayrollSummary } from '@/types/dashboard'

export interface FinanceSummary {
  totalOutstanding?: number
  collectedToday?: number
  totalRevenue?: number
  totalInvoices?: number
  pendingInvoices?: number
  partialInvoices?: number
  overdueInvoices?: number
  currency?: string
}

export interface FinancialReport {
  report_type?: string
  generated_at?: string
  currency_filter?: string
  total_invoices?: number
  outstanding?: number
  collected?: number
  by_status?: Record<string, number>
  transactions_count?: number
  records?: FinancialReportInvoice[]
}

export interface FinancialReportInvoice {
  invoice_number?: string
  student_id?: number
  amount?: number
  balance?: number
  status?: string
  currency?: string
}

export interface AgingBucket {
  label?: string
  total?: number
  count?: number
}

export interface AgingReport {
  generated_at?: string
  total_outstanding?: number
  buckets?: Record<string, AgingBucket>
  invoices?: Array<{
    invoice_number?: string
    student_name?: string
    balance?: number
    currency?: string
    days_past_due?: number
    bucket?: string
    status?: string
  }>
}

export interface ReconciliationReport {
  period?: string
  from?: string
  to?: string
  invoiced?: number
  collected?: number
  reversed?: number
  net_collected?: number
  by_payment_method?: Array<{ method?: string; count?: number; total?: number }>
  cash_total?: number
  bank_total?: number
  mobile_money_total?: number
}

export interface FeeCollectionPoint {
  month?: string
  total?: number
}

export interface FinanceDetails {
  summary: FinanceSummary | null
  financial: FinancialReport | null
  aging: AgingReport | null
  reconciliation: ReconciliationReport | null
  feeTrend: FeeCollectionPoint[]
  payroll: PayrollSummary | null
}

export const INVOICE_STATUS_LABELS: Record<string, string> = {
  pending: 'Pending',
  partial: 'Partially paid',
  paid: 'Paid',
  overdue: 'Overdue',
}
