import {
  ArrowLeftRight,
  Banknote,
  CreditCard,
  HardDrive,
  LineChart,
  Receipt,
  Scale,
  ShoppingCart,
  Tags,
  Wallet,
} from '@lucide/vue'
import type { ModuleHubTab } from '@/lib/module-hub'

export const FINANCE_HUB_TABS: ModuleHubTab[] = [
  {
    id: 'overview',
    title: 'Overview',
    description: 'Collections, cash movement, and the numbers that matter today.',
    icon: Wallet,
    panel: 'finance-overview',
  },
  {
    id: 'payments',
    title: 'Payments',
    description: 'Record and review fee collections.',
    icon: CreditCard,
    listKey: 'finance-payments',
  },
  {
    id: 'invoices',
    title: 'Invoices',
    description: 'Bill students and track outstanding balances.',
    icon: Receipt,
    listKey: 'finance-invoices',
  },
  {
    id: 'fees',
    title: 'Fees',
    description: 'Fee categories and class fee structures.',
    icon: Tags,
    sections: [
      {
        listKey: 'finance-fee-categories',
        title: 'Fee categories',
        description: 'Group fee types used on invoices and structures.',
      },
      {
        listKey: 'finance-fees',
        title: 'Fee structures',
        description: 'Configure amounts by class and term.',
      },
    ],
  },
  {
    id: 'transactions',
    title: 'Transactions',
    description: 'Full ledger — money in and out across currencies.',
    icon: ArrowLeftRight,
    component: () => import('@/modules/finance/views/TransactionsManagementView.vue'),
  },
  {
    id: 'payroll',
    title: 'Payroll',
    description: 'Generate payslips and pay staff.',
    icon: Banknote,
    component: () => import('@/modules/finance/views/PayrollManagementView.vue'),
  },
  {
    id: 'cash-flow',
    title: 'Cash flow',
    description: 'Money in vs money out so everything balances.',
    icon: Scale,
    component: () => import('@/modules/finance/views/FinanceCashFlowView.vue'),
  },
  {
    id: 'aging',
    title: 'Aging',
    description: 'Outstanding balances by due date.',
    icon: LineChart,
    component: () => import('@/modules/finance/views/FinanceReportsView.vue'),
  },
  {
    id: 'procurement',
    title: 'Spend',
    description: 'Spend requests and vendor payments.',
    icon: ShoppingCart,
    sections: [
      {
        listKey: 'ops-procurement',
        title: 'Spend requests',
        description: 'Request → approve → record payment.',
      },
      {
        listKey: 'ops-procurement-vendors',
        title: 'Vendors',
        description: 'Supplier records for procurement.',
      },
    ],
  },
  {
    id: 'assets',
    title: 'Assets',
    description: 'School assets and depreciation records.',
    icon: HardDrive,
    listKey: 'ops-assets',
  },
]

export const FINANCE_HUB_DEFAULT_TAB = 'overview'
