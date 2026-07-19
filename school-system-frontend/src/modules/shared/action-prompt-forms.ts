import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { PAYROLL_PAYMENT_METHOD_OPTIONS } from '@/lib/finance-constants'
import type { FormSheetSize } from '@/lib/form-standards'

export interface ActionPromptForm {
  title: string
  description?: string
  fields: FormFieldSchema[]
  schema: z.ZodTypeAny
  defaults?:
    | Record<string, unknown>
    | ((row: Record<string, unknown>) => Record<string, unknown>)
  saveLabel?: string
  size?: FormSheetSize
}

const MONTH_OPTIONS = [
  { label: 'January', value: '1' },
  { label: 'February', value: '2' },
  { label: 'March', value: '3' },
  { label: 'April', value: '4' },
  { label: 'May', value: '5' },
  { label: 'June', value: '6' },
  { label: 'July', value: '7' },
  { label: 'August', value: '8' },
  { label: 'September', value: '9' },
  { label: 'October', value: '10' },
  { label: 'November', value: '11' },
  { label: 'December', value: '12' },
]

export const payrollGeneratePromptForm: ActionPromptForm = {
  title: 'Generate payroll',
  description:
    'Creates or refreshes pending payslips for all active staff from their salary settings. Paid or partially paid rows for the same month are left unchanged.',
  saveLabel: 'Generate',
  size: 'md',
  fields: [
    {
      name: 'month',
      label: 'Month',
      type: 'select',
      required: true,
      options: MONTH_OPTIONS,
    },
    {
      name: 'year',
      label: 'Year',
      type: 'number',
      required: true,
      placeholder: String(new Date().getFullYear()),
    },
  ],
  schema: z.object({
    month: z.coerce.number().int().min(1).max(12),
    year: z.coerce.number().int().min(2020).max(2100),
  }),
  defaults: () => ({
    month: String(new Date().getMonth() + 1),
    year: new Date().getFullYear(),
  }),
}

export const payrollProcessPromptForm: ActionPromptForm = {
  title: 'Process payroll payment',
  description: 'Records payment against this payslip and posts a payroll expense transaction.',
  saveLabel: 'Record payment',
  size: 'md',
  fields: [
    {
      name: 'payment_method',
      label: 'Payment method',
      type: 'select',
      required: true,
      options: [...PAYROLL_PAYMENT_METHOD_OPTIONS],
    },
    {
      name: 'amount_paid',
      label: 'Amount to pay',
      type: 'number',
      required: true,
      description: 'Defaults to the remaining balance. Enter less for a partial payment.',
    },
    {
      name: 'payment_reference',
      label: 'Payment reference',
      type: 'text',
      placeholder: 'Optional bank or mobile money reference',
    },
    {
      name: 'paid_at',
      label: 'Payment date',
      type: 'date',
    },
  ],
  schema: z.object({
    payment_method: z.enum([
      'bank_transfer',
      'cash',
      'ecocash',
      'onemoney',
      'zipit',
      'swipe',
    ]),
    amount_paid: z.coerce.number().positive('Enter an amount greater than zero'),
    payment_reference: z.string().optional(),
    paid_at: z.string().optional(),
  }),
  defaults: (row) => {
    const net = Number(row.net_salary ?? 0)
    const paid = Number(row.amount_paid ?? 0)
    const remaining = Math.max(0, Number((net - paid).toFixed(2)))
    return {
      payment_method: 'bank_transfer',
      amount_paid: remaining,
      paid_at: new Date().toISOString().slice(0, 10),
      payment_reference: '',
    }
  },
}

export const inventoryRestockPromptForm: ActionPromptForm = {
  title: 'Restock inventory',
  description: 'Add stock to this item.',
  saveLabel: 'Restock',
  size: 'md',
  fields: [
    {
      name: 'quantity',
      label: 'Quantity to add',
      type: 'number',
      required: true,
      placeholder: '10',
    },
  ],
  schema: z.object({
    quantity: z.coerce.number().int().min(1, 'Enter at least 1'),
  }),
  defaults: { quantity: 10 },
}

export const spendDisbursePromptForm: ActionPromptForm = {
  title: 'Record spend payment',
  description:
    'Pays an approved request and posts the expense to the school ledger. Only do this after approval.',
  saveLabel: 'Record payment',
  size: 'md',
  fields: [
    {
      name: 'payment_method',
      label: 'Payment method',
      type: 'select',
      required: true,
      options: [...PAYROLL_PAYMENT_METHOD_OPTIONS, { label: 'Cheque', value: 'cheque' }],
    },
    {
      name: 'amount_paid',
      label: 'Amount to pay',
      type: 'number',
      required: true,
      description: 'Defaults to the estimated cost on the request.',
    },
    {
      name: 'payment_reference',
      label: 'Payment reference',
      type: 'text',
      placeholder: 'Optional bank or mobile money reference',
    },
    {
      name: 'paid_at',
      label: 'Payment date',
      type: 'date',
    },
  ],
  schema: z.object({
    payment_method: z.enum([
      'bank_transfer',
      'cash',
      'ecocash',
      'onemoney',
      'zipit',
      'swipe',
      'cheque',
    ]),
    amount_paid: z.coerce.number().positive('Enter an amount greater than zero'),
    payment_reference: z.string().optional(),
    paid_at: z.string().optional(),
  }),
  defaults: (row) => ({
    payment_method: 'bank_transfer',
    amount_paid: Number(row.estimated_cost ?? row.amount_paid ?? 0),
    paid_at: new Date().toISOString().slice(0, 10),
    payment_reference: '',
  }),
}

export const receiveGoodsPromptForm: ActionPromptForm = {
  title: 'Receive goods',
  description: 'Confirm delivery for an approved or paid purchase request.',
  saveLabel: 'Receive goods',
  size: 'md',
  fields: [
    {
      name: 'received_date',
      label: 'Received date',
      type: 'date',
      required: true,
    },
    {
      name: 'notes',
      label: 'Notes',
      type: 'textarea',
      placeholder: 'Optional delivery notes',
      colSpan: 2,
    },
  ],
  schema: z.object({
    received_date: z.string().min(1, 'Date is required'),
    notes: z.string().optional(),
  }),
  defaults: () => ({
    received_date: new Date().toISOString().slice(0, 10),
    notes: '',
  }),
}

