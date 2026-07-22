import type { Component } from 'vue'
import {
  Ban,
  Banknote,
  Check,
  CircleDot,
  Eye,
  FileText,
  Pencil,
  Receipt,
  Send,
  ShieldCheck,
  Trash2,
  X,
} from '@lucide/vue'

/** Canonical icons for table row actions — use these everywhere. */
export const TABLE_ACTION_ICONS = {
  edit: Pencil,
  delete: Trash2,
  view: Eye,
  approve: Check,
  reject: X,
  publish: Send,
  process: Banknote,
  receipt: Receipt,
  deactivate: Ban,
  permissions: ShieldCheck,
  details: FileText,
  more: CircleDot,
} as const

const LABEL_ICON_MAP: Array<{ match: RegExp; icon: Component }> = [
  { match: /^edit\b/i, icon: TABLE_ACTION_ICONS.edit },
  { match: /^delete\b|^remove\b/i, icon: TABLE_ACTION_ICONS.delete },
  { match: /^view\b|^open\b|^details\b/i, icon: TABLE_ACTION_ICONS.view },
  { match: /^approve\b/i, icon: TABLE_ACTION_ICONS.approve },
  { match: /^reject\b|^decline\b/i, icon: TABLE_ACTION_ICONS.reject },
  { match: /^publish\b/i, icon: TABLE_ACTION_ICONS.publish },
  { match: /marks|matrix|enter marks/i, icon: TABLE_ACTION_ICONS.details },
  { match: /payment|process|payslip|payroll|disburse/i, icon: TABLE_ACTION_ICONS.process },
  { match: /receipt/i, icon: TABLE_ACTION_ICONS.receipt },
  { match: /deactivat|activat/i, icon: TABLE_ACTION_ICONS.deactivate },
  { match: /permission|access/i, icon: TABLE_ACTION_ICONS.permissions },
]

export function iconForActionLabel(label: string): Component {
  const found = LABEL_ICON_MAP.find((entry) => entry.match.test(label))
  return found?.icon ?? TABLE_ACTION_ICONS.more
}

export const tableActionIconClass = 'size-4 shrink-0'
export const tableActionMenuIconClass = 'mr-2 size-4 shrink-0'
