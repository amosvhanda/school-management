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
import PageShell from '@/components/layout/PageShell.vue'
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
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate, formatDateTime, formatRelativeTime } from '@/lib/format'
import {
  platformApi,
  usersApi,
  type SchoolLicenseRow,
} from '@/services/api.service'

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
  school_id?: number | null
  assigned_to?: number | null
  assigned_by?: number | null
  priority?: string
  status?: string
  due_date?: string | null
  progress_percent?: number | null
  completed_at?: string | null
  created_at?: string | null
  school?: { id?: number; name?: string; code?: string } | null
  assignee?: StaffUser | null
  assigner?: StaffUser | null
}

const toast = useToast()
const loading = ref(true)
const error = ref<string | null>(null)
const tasks = ref<StaffTask[]>([])
const schools = ref<SchoolLicenseRow[]>([])
const staffOptions = ref<StaffUser[]>([])
const loadingStaff = ref(false)

const schoolFilter = ref('all')
const statusFilter = ref('all')
const priorityFilter = ref('all')
const search = ref('')

const createOpen = ref(false)
const detailOpen = ref(false)
const creating = ref(false)
const updatingId = ref<number | null>(null)
const selected = ref<StaffTask | null>(null)

const createForm = ref({
  school_id: '',
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
  return tasks.value.filter((task) => {
    if (schoolFilter.value !== 'all' && String(task.school_id ?? '') !== schoolFilter.value) {
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
      task.school?.name,
      task.school?.code,
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

function roleLabel(role?: string) {
  return labelize(role)
}

async function loadStaffForSchool(schoolId: string) {
  if (!schoolId) {
    staffOptions.value = []
    return
  }
  loadingStaff.value = true
  try {
    const users = await usersApi.list({
      school_id: schoolId,
      role: staffRoles,
      status: 'active',
    })
    staffOptions.value = Array.isArray(users) ? (users as StaffUser[]) : []
  } catch {
    staffOptions.value = []
    toast.error('Could not load staff for this school')
  } finally {
    loadingStaff.value = false
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [tasksPayload, schoolsPayload] = await Promise.all([
      platformApi.staffTasks(),
      platformApi.schoolsOverview().catch(() => null),
    ])
    tasks.value = Array.isArray(tasksPayload) ? (tasksPayload as StaffTask[]) : []
    schools.value = Array.isArray(schoolsPayload?.schools) ? schoolsPayload.schools : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load staff tasks')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  createForm.value = {
    school_id: schoolFilter.value !== 'all'
      ? schoolFilter.value
      : (schools.value[0] ? String(schools.value[0].id) : ''),
    title: '',
    description: '',
    assigned_to: '',
    priority: 'normal',
    due_date: '',
  }
  createOpen.value = true
}

function openDetail(task: StaffTask) {
  selected.value = task
  detailOpen.value = true
}

async function submitCreate() {
  if (!createForm.value.school_id) {
    toast.error('Select a school')
    return
  }
  if (!createForm.value.assigned_to) {
    toast.error('Select an assignee')
    return
  }
  creating.value = true
  try {
    await platformApi.createStaffTask({
      school_id: Number(createForm.value.school_id),
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

watch(
  () => createForm.value.school_id,
  (schoolId) => {
    createForm.value.assigned_to = ''
    void loadStaffForSchool(schoolId)
  },
)

onMounted(load)
</script>

<template>
  <PageShell
    title="Staff tasks"
    description="Assign and track operational work across schools — reports, follow-ups, and overdue actions."
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" :disabled="loading" @click="load">
        <RefreshCw class="size-4" aria-hidden="true" />
        Refresh
      </Button>
      <Button :disabled="!schools.length" @click="openCreate">
        <Plus class="size-4" aria-hidden="true" />
        Assign task
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading staff tasks" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Open tasks"
          :value="String(openCount)"
          subtitle="Pending or in progress"
          :icon="ListTodo"
        />
        <KpiCard
          title="Overdue"
          :value="String(overdueCount)"
          subtitle="Past due date"
          :icon="AlertTriangle"
          :accent="overdueCount > 0 ? 'danger' : undefined"
        />
        <KpiCard
          title="Urgent"
          :value="String(urgentCount)"
          subtitle="Open urgent priority"
          :icon="Clock3"
          :accent="urgentCount > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Completed"
          :value="String(completedCount)"
          subtitle="Closed successfully"
          :icon="CheckCircle2"
          accent="success"
        />
      </div>

      <Card class="mt-6">
        <CardHeader class="gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <CardTitle class="text-base">Task board</CardTitle>
            <CardDescription>
              Filter by school, status, and priority — then update progress or mark complete
            </CardDescription>
          </div>
          <div class="grid w-full gap-3 sm:max-w-3xl sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-2 sm:col-span-2 lg:col-span-1">
              <Label for="task-search">Search</Label>
              <Input
                id="task-search"
                v-model="search"
                type="search"
                placeholder="Title, school, assignee…"
              />
            </div>
            <div class="space-y-2">
              <Label for="school-filter">School</Label>
              <Select v-model="schoolFilter">
                <SelectTrigger id="school-filter">
                  <SelectValue placeholder="All schools" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All schools</SelectItem>
                  <SelectItem
                    v-for="school in schools"
                    :key="school.id"
                    :value="String(school.id)"
                  >
                    {{ school.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="status-filter">Status</Label>
              <Select v-model="statusFilter">
                <SelectTrigger id="status-filter">
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All statuses</SelectItem>
                  <SelectItem
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                  >
                    {{ status.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="priority-filter">Priority</Label>
              <Select v-model="priorityFilter">
                <SelectTrigger id="priority-filter">
                  <SelectValue placeholder="All priorities" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All priorities</SelectItem>
                  <SelectItem
                    v-for="priority in priorities"
                    :key="priority.value"
                    :value="priority.value"
                  >
                    {{ priority.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="filteredTasks.length" class="divide-y rounded-lg border">
            <article
              v-for="task in filteredTasks"
              :key="task.id"
              class="flex flex-col gap-3 p-4 lg:flex-row lg:items-start lg:justify-between"
            >
              <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-sm font-medium">{{ task.title || 'Untitled task' }}</h3>
                  <Badge :variant="statusVariant(task.status)" class="font-normal capitalize">
                    {{ labelize(task.status) }}
                  </Badge>
                  <Badge :variant="priorityVariant(task.priority)" class="font-normal capitalize">
                    {{ labelize(task.priority) }}
                  </Badge>
                  <Badge
                    v-if="isOverdue(task)"
                    variant="destructive"
                    class="font-normal"
                  >
                    Overdue
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ task.school?.name || task.school?.code || 'School' }}
                  <span v-if="task.school?.code"> ({{ task.school.code }})</span>
                  · Assigned to {{ task.assignee?.name || '—' }}
                  <span v-if="task.assignee?.role"> ({{ roleLabel(task.assignee.role) }})</span>
                </p>
                <p class="text-xs text-muted-foreground">
                  <span v-if="task.due_date">
                    Due {{ formatDate(task.due_date) }}
                  </span>
                  <span v-else>No due date</span>
                  · {{ task.progress_percent ?? 0 }}% complete
                  <span v-if="task.assigner?.name">
                    · By {{ task.assigner.name }}
                  </span>
                </p>
                <div
                  class="h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-muted"
                  role="progressbar"
                  :aria-valuenow="task.progress_percent ?? 0"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="`Progress ${task.progress_percent ?? 0} percent`"
                >
                  <div
                    class="h-full rounded-full bg-primary transition-[width]"
                    :style="{ width: `${Math.min(100, Math.max(0, task.progress_percent ?? 0))}%` }"
                  />
                </div>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" @click="openDetail(task)">
                  View
                </Button>
                <Button
                  v-if="task.status === 'pending'"
                  size="sm"
                  variant="secondary"
                  :disabled="updatingId === task.id"
                  @click="updateTask(task, { status: 'in_progress', progress_percent: Math.max(task.progress_percent ?? 0, 10) })"
                >
                  <CircleDot class="size-4" aria-hidden="true" />
                  Start
                </Button>
                <Button
                  v-if="isOpen(task)"
                  size="sm"
                  :disabled="updatingId === task.id"
                  @click="updateTask(task, { status: 'completed' })"
                >
                  <CheckCircle2 class="size-4" aria-hidden="true" />
                  Complete
                </Button>
              </div>
            </article>
          </div>
          <div
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
          >
            <p>No staff tasks match these filters.</p>
            <Button class="mt-4" variant="outline" :disabled="!schools.length" @click="openCreate">
              Assign the first task
            </Button>
          </div>
        </CardContent>
      </Card>
    </template>

    <Dialog v-model:open="createOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Assign staff task</DialogTitle>
          <DialogDescription>
            Create an operational task for a staff member at a specific school.
          </DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCreate">
          <div class="space-y-2">
            <Label for="create-school">School</Label>
            <Select v-model="createForm.school_id">
              <SelectTrigger id="create-school">
                <SelectValue placeholder="Select school" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="school in schools"
                  :key="school.id"
                  :value="String(school.id)"
                >
                  {{ school.name }} ({{ school.code }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="create-assignee">Assignee</Label>
            <Select v-model="createForm.assigned_to" :disabled="!createForm.school_id || loadingStaff">
              <SelectTrigger id="create-assignee">
                <SelectValue :placeholder="loadingStaff ? 'Loading staff…' : 'Select staff member'" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="user in staffOptions"
                  :key="user.id"
                  :value="String(user.id)"
                >
                  {{ user.name }} — {{ roleLabel(user.role) }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="createForm.school_id && !loadingStaff && !staffOptions.length" class="text-xs text-muted-foreground">
              No active staff found for this school.
            </p>
          </div>
          <div class="space-y-2">
            <Label for="create-title">Title</Label>
            <Input id="create-title" v-model="createForm.title" required maxlength="255" />
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="create-priority">Priority</Label>
              <Select v-model="createForm.priority">
                <SelectTrigger id="create-priority">
                  <SelectValue placeholder="Priority" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="priority in priorities"
                    :key="priority.value"
                    :value="priority.value"
                  >
                    {{ priority.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="create-due">Due date</Label>
              <Input id="create-due" v-model="createForm.due_date" type="date" />
            </div>
          </div>
          <div class="space-y-2">
            <Label for="create-description">Description</Label>
            <Textarea
              id="create-description"
              v-model="createForm.description"
              rows="4"
              placeholder="What needs to be done, and any context for the assignee…"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
            <Button type="submit" :disabled="creating || !staffOptions.length">
              {{ creating ? 'Assigning…' : 'Assign task' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="detailOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{{ selected?.title || 'Task' }}</DialogTitle>
          <DialogDescription>
            {{ selected?.school?.name || 'School' }}
            · {{ selected?.assignee?.name || 'Unassigned' }}
          </DialogDescription>
        </DialogHeader>
        <div v-if="selected" class="space-y-4">
          <div class="flex flex-wrap gap-2">
            <Badge :variant="statusVariant(selected.status)" class="font-normal capitalize">
              {{ labelize(selected.status) }}
            </Badge>
            <Badge :variant="priorityVariant(selected.priority)" class="font-normal capitalize">
              {{ labelize(selected.priority) }}
            </Badge>
            <Badge v-if="isOverdue(selected)" variant="destructive" class="font-normal">
              Overdue
            </Badge>
          </div>

          <div class="rounded-lg border bg-muted/30 p-4">
            <p class="whitespace-pre-wrap text-sm">
              {{ selected.description || 'No description provided.' }}
            </p>
          </div>

          <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-muted-foreground">Assignee</dt>
              <dd class="font-medium">
                {{ selected.assignee?.name || '—' }}
                <span v-if="selected.assignee?.email" class="block text-xs font-normal text-muted-foreground">
                  {{ selected.assignee.email }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Assigned by</dt>
              <dd class="font-medium">{{ selected.assigner?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Due date</dt>
              <dd class="font-medium">
                {{ selected.due_date ? formatDate(selected.due_date) : 'None' }}
              </dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Progress</dt>
              <dd class="font-medium">{{ selected.progress_percent ?? 0 }}%</dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Created</dt>
              <dd class="font-medium">
                <span v-if="selected.created_at" :title="formatDateTime(selected.created_at)">
                  {{ formatRelativeTime(selected.created_at) }}
                </span>
                <span v-else>—</span>
              </dd>
            </div>
            <div>
              <dt class="text-muted-foreground">Completed</dt>
              <dd class="font-medium">
                {{ selected.completed_at ? formatDateTime(selected.completed_at) : '—' }}
              </dd>
            </div>
          </dl>

          <div v-if="isOpen(selected)" class="space-y-3 rounded-lg border p-4">
            <Label for="detail-status">Update status</Label>
            <div class="grid gap-3 sm:grid-cols-2">
              <Select
                :model-value="selected.status ?? 'pending'"
                @update:model-value="(value) => updateTask(selected!, { status: String(value) })"
              >
                <SelectTrigger id="detail-status">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="status in statuses.filter((s) => s.value !== 'cancelled')"
                    :key="status.value"
                    :value="status.value"
                  >
                    {{ status.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <Select
                :model-value="String(selected.progress_percent ?? 0)"
                @update:model-value="(value) => updateTask(selected!, { progress_percent: Number(value) })"
              >
                <SelectTrigger aria-label="Progress percent">
                  <SelectValue placeholder="Progress" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="step in [0, 25, 50, 75, 100]"
                    :key="step"
                    :value="String(step)"
                  >
                    {{ step }}%
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <DialogFooter class="flex-wrap gap-2">
            <Button variant="outline" @click="detailOpen = false">Close</Button>
            <Button
              v-if="isOpen(selected)"
              variant="outline"
              :disabled="updatingId === selected.id"
              @click="updateTask(selected, { status: 'cancelled' })"
            >
              Cancel task
            </Button>
            <Button
              v-if="isOpen(selected)"
              :disabled="updatingId === selected.id"
              @click="updateTask(selected, { status: 'completed' })"
            >
              <CheckCircle2 class="size-4" aria-hidden="true" />
              Mark complete
            </Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
