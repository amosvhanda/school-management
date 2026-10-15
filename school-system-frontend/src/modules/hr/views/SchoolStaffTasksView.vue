<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
  AlertTriangle,
  CheckCircle2,
  CircleDot,
  Clock3,
  ListTodo,
  Plus,
  RefreshCw,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate, formatRelativeTime } from '@/lib/format'
import { platformApi, usersApi } from '@/services/api.service'

const props = withDefaults(
  defineProps<{
    /** manage = school inbox + assign; mine = assignee view */
    mode?: 'manage' | 'mine'
  }>(),
  { mode: 'manage' },
)

interface StaffUser {
  id: number
  name?: string
  email?: string
  role?: string
  school_id?: number | null
}

interface StaffTask {
  id: number
  title?: string
  description?: string | null
  assigned_to?: number | null
  priority?: string
  status?: string
  due_date?: string | null
  progress_percent?: number | null
  completed_at?: string | null
  created_at?: string | null
  assignee?: StaffUser | null
  assigner?: StaffUser | null
}

const toast = useToast()
const { user, checkCapability } = useAuth()
const canManage = computed(
  () => props.mode === 'manage' && checkCapability('canManageTeachers'),
)

const loading = ref(true)
const error = ref<string | null>(null)
const tasks = ref<StaffTask[]>([])
const staffOptions = ref<StaffUser[]>([])
const loadingStaff = ref(false)

const scope = ref<'all' | 'mine'>(props.mode === 'mine' ? 'mine' : 'all')
const statusFilter = ref('all')
const priorityFilter = ref('all')
const search = ref('')

const createOpen = ref(false)
const detailOpen = ref(false)
const creating = ref(false)
const updatingId = ref<number | null>(null)
const selected = ref<StaffTask | null>(null)

const createForm = ref({
  title: '',
  description: '',
  assigned_to: '',
  priority: 'normal',
  due_date: '',
})

const priorities = [
  { value: 'low', label: 'Low' },
  { value: 'normal', label: 'Normal' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]

const statuses = [
  { value: 'pending', label: 'Pending' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
]

const staffRoles = [
  'admin',
  'school_admin',
  'teacher',
  'finance',
  'accounts',
  'examination_officer',
  'receptionist',
].join(',')

function startOfToday() {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d
}

function isOverdue(task: StaffTask) {
  if (!task.due_date) return false
  if (task.status === 'completed' || task.status === 'cancelled') return false
  const due = new Date(task.due_date)
  due.setHours(0, 0, 0, 0)
  return due < startOfToday()
}

function isOpen(task: StaffTask) {
  return task.status === 'pending' || task.status === 'in_progress'
}

const openCount = computed(() => tasks.value.filter(isOpen).length)
const overdueCount = computed(() => tasks.value.filter(isOverdue).length)
const completedCount = computed(() =>
  tasks.value.filter((task) => task.status === 'completed').length,
)
const urgentCount = computed(() =>
  tasks.value.filter((task) => isOpen(task) && task.priority === 'urgent').length,
)

const filteredTasks = computed(() => {
  const q = search.value.trim().toLowerCase()
  const uid = user.value?.id
  return tasks.value.filter((task) => {
    if (scope.value === 'mine' && uid != null && Number(task.assigned_to) !== Number(uid)) {
      return false
    }
    if (statusFilter.value !== 'all' && String(task.status ?? '') !== statusFilter.value) {
      return false
    }
    if (priorityFilter.value !== 'all' && String(task.priority ?? '') !== priorityFilter.value) {
      return false
    }
    if (!q) return true
    const haystack = [
      task.title,
      task.description,
      task.assignee?.name,
      task.assignee?.email,
      task.status,
      task.priority,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return haystack.includes(q)
  })
})

function statusVariant(status?: string): 'default' | 'secondary' | 'outline' | 'destructive' {
  if (status === 'completed') return 'default'
  if (status === 'cancelled') return 'outline'
  if (status === 'in_progress') return 'secondary'
  return 'outline'
}

function priorityVariant(priority?: string): 'default' | 'secondary' | 'outline' | 'destructive' {
  if (priority === 'urgent') return 'destructive'
  if (priority === 'high') return 'secondary'
  return 'outline'
}

function labelize(value?: string | null) {
  return String(value ?? '—').replace(/_/g, ' ')
}

async function loadStaff() {
  const schoolId = user.value?.school_id
  if (!schoolId) {
    staffOptions.value = []
    return
  }
  loadingStaff.value = true
  try {
    const users = await usersApi.list({
      school_id: String(schoolId),
      role: staffRoles,
      status: 'active',
    })
    staffOptions.value = Array.isArray(users) ? (users as StaffUser[]) : []
  } catch {
    staffOptions.value = []
  } finally {
    loadingStaff.value = false
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const params: Record<string, string | boolean> = {}
    if (props.mode === 'mine' || scope.value === 'mine') params.mine = true
    const payload = await platformApi.staffTasks(params)
    tasks.value = Array.isArray(payload) ? (payload as StaffTask[]) : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load staff tasks')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  createForm.value = {
    title: '',
    description: '',
    assigned_to: '',
    priority: 'normal',
    due_date: '',
  }
  createOpen.value = true
  void loadStaff()
}

function openDetail(task: StaffTask) {
  selected.value = task
  detailOpen.value = true
}

async function submitCreate() {
  if (!createForm.value.assigned_to) {
    toast.error('Select an assignee')
    return
  }
  const schoolId = user.value?.school_id
  if (!schoolId) {
    toast.error('No school on this account')
    return
  }
  creating.value = true
  try {
    await platformApi.createStaffTask({
      school_id: Number(schoolId),
      title: createForm.value.title,
      description: createForm.value.description || null,
      assigned_to: Number(createForm.value.assigned_to),
      priority: createForm.value.priority,
      due_date: createForm.value.due_date || null,
    })
    toast.success('Task assigned')
    createOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not create task', getErrorMessage(err))
  } finally {
    creating.value = false
  }
}

async function updateTask(task: StaffTask, payload: Record<string, unknown>) {
  updatingId.value = task.id
  try {
    await platformApi.updateStaffTask(task.id, payload)
    toast.success('Task updated')
    await load()
    if (selected.value?.id === task.id) {
      selected.value = tasks.value.find((row) => row.id === task.id) ?? null
    }
  } catch (err) {
    toast.error('Could not update task', getErrorMessage(err))
  } finally {
    updatingId.value = null
  }
}

watch(scope, () => {
  void load()
})

onMounted(load)
</script>

<template>
  <section class="space-y-4" :aria-label="mode === 'mine' ? 'My tasks' : 'Staff tasks'">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h2 class="text-lg font-semibold">{{ mode === 'mine' ? 'My tasks' : 'Staff tasks' }}</h2>
        <p class="text-sm text-muted-foreground">
          {{
            mode === 'mine'
              ? 'Work assigned to you — update progress and mark complete.'
              : 'Assign and track operational work for staff at this school.'
          }}
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button type="button" variant="outline" size="sm" :disabled="loading" @click="load">
          <RefreshCw class="mr-2 h-4 w-4" aria-hidden="true" />
          Refresh
        </Button>
        <Button v-if="canManage" type="button" size="sm" @click="openCreate">
          <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
          Assign task
        </Button>
      </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <KpiCard title="Open" :value="String(openCount)" subtitle="Pending or in progress" :icon="CircleDot" />
      <KpiCard
        title="Overdue"
        :value="String(overdueCount)"
        subtitle="Past due date"
        :icon="AlertTriangle"
        :accent="overdueCount > 0 ? 'danger' : undefined"
      />
      <KpiCard title="Urgent" :value="String(urgentCount)" subtitle="Open urgent tasks" :icon="ListTodo" />
      <KpiCard title="Completed" :value="String(completedCount)" subtitle="Done" :icon="CheckCircle2" />
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <Label for="task-search" class="sr-only">Search tasks</Label>
      <Input id="task-search" v-model="search" placeholder="Search tasks…" class="max-w-xs" />
      <Select v-if="canManage" v-model="scope">
        <SelectTrigger class="w-[140px]" aria-label="Scope">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All school</SelectItem>
          <SelectItem value="mine">Assigned to me</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="statusFilter">
        <SelectTrigger class="w-[150px]" aria-label="Status filter">
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All statuses</SelectItem>
          <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
        </SelectContent>
      </Select>
      <Select v-model="priorityFilter">
        <SelectTrigger class="w-[150px]" aria-label="Priority filter">
          <SelectValue placeholder="Priority" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="all">All priorities</SelectItem>
          <SelectItem v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <PageLoader v-if="loading" label="Loading staff tasks" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <EmptyState
      v-else-if="!filteredTasks.length"
      variant="embedded"
      title="No tasks"
      description="No staff tasks match this filter."
    />
    <div v-else class="grid gap-3">
      <Card
        v-for="task in filteredTasks"
        :key="task.id"
        class="cursor-pointer transition-colors hover:bg-muted/20"
        @click="openDetail(task)"
      >
        <CardHeader class="pb-2">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="space-y-1">
              <CardTitle class="text-base">{{ task.title }}</CardTitle>
              <CardDescription>
                {{ task.assignee?.name ?? 'Unassigned' }}
                <span v-if="task.due_date"> · Due {{ formatDate(task.due_date) }}</span>
                <span v-if="task.created_at"> · {{ formatRelativeTime(task.created_at) }}</span>
              </CardDescription>
            </div>
            <div class="flex flex-wrap gap-1">
              <Badge :variant="priorityVariant(task.priority)" class="capitalize">{{ labelize(task.priority) }}</Badge>
              <Badge :variant="statusVariant(task.status)" class="capitalize">{{ labelize(task.status) }}</Badge>
              <Badge v-if="isOverdue(task)" variant="destructive">Overdue</Badge>
            </div>
          </div>
        </CardHeader>
        <CardContent v-if="task.description" class="pt-0">
          <p class="line-clamp-2 text-sm text-muted-foreground">{{ task.description }}</p>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="createOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Assign task</DialogTitle>
          <DialogDescription>Create a task for a staff member at this school.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCreate">
          <div class="space-y-2">
            <Label for="task-title">Title</Label>
            <Input id="task-title" v-model="createForm.title" required maxlength="255" />
          </div>
          <div class="space-y-2">
            <Label for="task-assignee">Assignee</Label>
            <Select v-model="createForm.assigned_to" :disabled="loadingStaff">
              <SelectTrigger id="task-assignee">
                <SelectValue :placeholder="loadingStaff ? 'Loading staff…' : 'Select staff'" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="member in staffOptions" :key="member.id" :value="String(member.id)">
                  {{ member.name }}{{ member.role ? ` (${labelize(member.role)})` : '' }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="task-priority">Priority</Label>
              <Select v-model="createForm.priority">
                <SelectTrigger id="task-priority">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="task-due">Due date</Label>
              <Input id="task-due" v-model="createForm.due_date" type="date" />
            </div>
          </div>
          <div class="space-y-2">
            <Label for="task-desc">Description</Label>
            <Textarea id="task-desc" v-model="createForm.description" rows="3" />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
            <Button type="submit" :disabled="creating || !createForm.title.trim() || !createForm.assigned_to">
              {{ creating ? 'Assigning…' : 'Assign task' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="detailOpen">
      <DialogContent class="sm:max-w-lg" v-if="selected">
        <DialogHeader>
          <DialogTitle>{{ selected.title }}</DialogTitle>
          <DialogDescription>
            Assigned to {{ selected.assignee?.name ?? '—' }}
            <span v-if="selected.assigner?.name"> by {{ selected.assigner.name }}</span>
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <p v-if="selected.description" class="whitespace-pre-wrap text-sm">{{ selected.description }}</p>
          <div class="flex flex-wrap gap-2">
            <Badge :variant="priorityVariant(selected.priority)" class="capitalize">{{ labelize(selected.priority) }}</Badge>
            <Badge :variant="statusVariant(selected.status)" class="capitalize">{{ labelize(selected.status) }}</Badge>
            <Badge v-if="selected.due_date" variant="outline">Due {{ formatDate(selected.due_date) }}</Badge>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button
              v-if="selected.status === 'pending'"
              size="sm"
              :disabled="updatingId === selected.id"
              @click="updateTask(selected, { status: 'in_progress', progress_percent: 25 })"
            >
              <Clock3 class="mr-2 h-4 w-4" aria-hidden="true" />
              Start
            </Button>
            <Button
              v-if="selected.status !== 'completed' && selected.status !== 'cancelled'"
              size="sm"
              :disabled="updatingId === selected.id"
              @click="updateTask(selected, { status: 'completed', progress_percent: 100 })"
            >
              <CheckCircle2 class="mr-2 h-4 w-4" aria-hidden="true" />
              Complete
            </Button>
            <Button
              v-if="selected.status !== 'cancelled' && selected.status !== 'completed'"
              size="sm"
              variant="outline"
              :disabled="updatingId === selected.id"
              @click="updateTask(selected, { status: 'cancelled' })"
            >
              Cancel task
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </section>
</template>
