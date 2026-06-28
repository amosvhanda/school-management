/** Zimbabwe school payment methods */
export const PAYMENT_METHOD_OPTIONS = [
  { label: 'Cash', value: 'cash' },
  { label: 'EcoCash', value: 'ecocash' },
  { label: 'OneMoney', value: 'onemoney' },
  { label: 'InnBucks', value: 'innbucks' },
  { label: 'Bank transfer', value: 'bank_transfer' },
  { label: 'Card', value: 'card' },
  { label: 'Cheque', value: 'cheque' },
  { label: 'Other', value: 'other' },
] as const

export const INVOICE_STATUS_OPTIONS = [
  { label: 'Pending', value: 'pending' },
  { label: 'Partial', value: 'partial' },
  { label: 'Paid', value: 'paid' },
  { label: 'Overdue', value: 'overdue' },
] as const

export const PAYMENT_STATUS_OPTIONS = [
  { label: 'Completed', value: 'completed' },
  { label: 'Pending', value: 'pending' },
  { label: 'Reversed', value: 'reversed' },
] as const

export function formatMoney(amount: unknown, currency = 'USD'): string {
  const value = Number(amount ?? 0)
  if (!Number.isFinite(value)) return `${currency} 0.00`
  return `${currency} ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

export function formatPaymentMethod(method: unknown): string {
  const key = String(method ?? '').toLowerCase()
  return PAYMENT_METHOD_OPTIONS.find((o) => o.value === key)?.label ?? String(method ?? '—')
}
