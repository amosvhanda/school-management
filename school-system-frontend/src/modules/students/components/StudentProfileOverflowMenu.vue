<script setup lang="ts">
import { ref } from 'vue'
import {
  ArrowLeftRight,
  ArrowUpRight,
  Ban,
  BookOpen,
  CalendarCheck2,
  CalendarMinus,
  ClipboardList,
  FileText,
  Library,
  Lock,
  MoreVertical,
  Archive,
  Wallet,
} from '@lucide/vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { studentsApi } from '@/services/api.service'
import type { StudentProfileTabId } from '@/modules/students/student-profile-tabs'

const props = defineProps<{
  studentId: string | number
  studentName?: string
  studentEmail?: string | null
  admissionNo?: string | null
  canManage?: boolean
}>()

const emit = defineEmits<{
  navigate: [tab: StudentProfileTabId]
  refreshed: []
}>()

const toast = useToast()
const busy = ref(false)

const recordItems: Array<{
  label: string
  icon: typeof CalendarCheck2
  tab: StudentProfileTabId
}> = [
  { label: 'Attendance', icon: CalendarCheck2, tab: 'attendance' },
  { label: 'Fees', icon: Wallet, tab: 'fees' },
  { label: 'Exam Results', icon: ClipboardList, tab: 'exams' },
  { label: 'Time Table', icon: BookOpen, tab: 'timetable' },
  { label: 'Leaves', icon: CalendarMinus, tab: 'attendance' },
  { label: 'Library', icon: Library, tab: 'library' },
  { label: 'Documents', icon: FileText, tab: 'documents' },
  { label: 'Wallet', icon: Wallet, tab: 'fees' },
]

function goTo(tab: StudentProfileTabId) {
  emit('navigate', tab)
}

function showLoginDetails() {
  const lines = [
    props.studentName ? `Student: ${props.studentName}` : null,
    props.admissionNo ? `Admission #: ${props.admissionNo}` : null,
    props.studentEmail ? `Portal email: ${props.studentEmail}` : 'No portal email on file',
  ].filter(Boolean)
  toast.info('Login details', lines.join(' · '))
}

async function runLifecycle(
  action: 'suspend' | 'transfer_out' | 'withdraw',
  confirmMessage: string,
  successMessage: string,
) {
  if (!props.canManage || busy.value) return
  if (!window.confirm(confirmMessage)) return
  busy.value = true
  try {
    await studentsApi.lifecycleTransition(props.studentId, {
      action,
      reason: `Profile menu: ${action}`,
      issue_tc: action === 'transfer_out' || action === 'withdraw',
      deactivate_account: true,
    })
    toast.success(successMessage)
    emit('refreshed')
  } catch (err) {
    toast.error('Action failed', getErrorMessage(err))
  } finally {
    busy.value = false
  }
}

async function promoteStudent() {
  if (!props.canManage || busy.value) return
  if (!window.confirm(`Promote ${props.studentName ?? 'this student'} for the next academic year?`)) return
  busy.value = true
  try {
    const year = new Date().getFullYear()
    await studentsApi.lifecyclePromote(props.studentId, {
      academic_year: String(year),
      next_academic_year: String(year + 1),
    })
    toast.success('Promotion decision applied')
    emit('refreshed')
  } catch (err) {
    toast.error('Promotion failed', getErrorMessage(err))
  } finally {
    busy.value = false
  }
}

function transferStudent() {
  void runLifecycle(
    'transfer_out',
    `Transfer ${props.studentName ?? 'this student'} out of the school? Outstanding fees must be cleared.`,
    'Transfer recorded',
  )
}

function suspendStudent() {
  void runLifecycle(
    'suspend',
    `Suspend ${props.studentName ?? 'this student'}? Portal access will be deactivated.`,
    'Student suspended',
  )
}

function archiveStudent() {
  void runLifecycle(
    'withdraw',
    `Archive / withdraw ${props.studentName ?? 'this student'}? This exits the active roll.`,
    'Student archived',
  )
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        type="button"
        variant="outline"
        size="icon"
        class="rounded-xl"
        :disabled="busy"
        :aria-busy="busy"
        aria-label="More student actions"
      >
        <MoreVertical class="size-4" aria-hidden="true" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-56 rounded-xl p-1.5">
      <DropdownMenuLabel class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
        Student records
      </DropdownMenuLabel>
      <DropdownMenuItem
        v-for="item in recordItems"
        :key="item.label"
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        @click="goTo(item.tab)"
      >
        <component :is="item.icon" class="size-4 text-muted-foreground" aria-hidden="true" />
        {{ item.label }}
      </DropdownMenuItem>

      <DropdownMenuSeparator class="my-1.5" />

      <DropdownMenuLabel class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
        Actions
      </DropdownMenuLabel>
      <DropdownMenuItem
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        :disabled="!canManage || busy"
        @click="promoteStudent"
      >
        <ArrowUpRight class="size-4 text-muted-foreground" aria-hidden="true" />
        Promote Student
      </DropdownMenuItem>
      <DropdownMenuItem
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        :disabled="!canManage || busy"
        @click="transferStudent"
      >
        <ArrowLeftRight class="size-4 text-muted-foreground" aria-hidden="true" />
        Transfer Student
      </DropdownMenuItem>
      <DropdownMenuItem
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        @click="showLoginDetails"
      >
        <Lock class="size-4 text-muted-foreground" aria-hidden="true" />
        Login Details
      </DropdownMenuItem>
      <DropdownMenuItem
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        :disabled="!canManage || busy"
        @click="suspendStudent"
      >
        <Ban class="size-4 text-muted-foreground" aria-hidden="true" />
        Suspend Student
      </DropdownMenuItem>
      <DropdownMenuItem
        variant="destructive"
        class="cursor-pointer gap-2 rounded-lg px-2 py-2"
        :disabled="!canManage || busy"
        @click="archiveStudent"
      >
        <Archive class="size-4" aria-hidden="true" />
        Archive Student
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
