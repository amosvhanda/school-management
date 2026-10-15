<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  Banknote,
  BedDouble,
  Bus,
  ClipboardCheck,
  Download,
  GraduationCap,
  LayoutGrid,
  List,
  Plus,
  Printer,
  RefreshCw,
  Search,
  TrendingUp,
  UserMinus,
  UserRound,
} from '@lucide/vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatMoney } from '@/lib/finance-constants'
import { cn } from '@/lib/utils'
import RegistrySection from '@/modules/shared/RegistrySection.vue'
import StudentGridCard from '@/modules/students/components/StudentGridCard.vue'
import { moduleEndpoints } from '@/services'
import { fetchKpis, fetchPaginatedList } from '@/services/dashboard.service'
import type { DashboardKpis } from '@/types/dashboard'
import type { ListQueryParams, Paginator } from '@/types/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const { school } = useSchoolProfile()

const kpis = ref<DashboardKpis | null>(null)
const students = ref<Record<string, unknown>[]>([])
const meta = ref<Pick<Paginator<unknown>, 'current_page' | 'last_page' | 'total'>>({
  current_page: 1,
  last_page: 1,
  total: 0,
})
const loading = ref(true)
const error = ref<string | null>(null)
const searchQuery = ref(typeof route.query.search === 'string' ? route.query.search : '')
const statusFilter = ref(typeof route.query.status === 'string' ? route.query.status : 'active')
const viewMode = ref<'grid' | 'list'>(route.query.view === 'list' ? 'list' : 'grid')
const selectedIds = ref<Set<string | number>>(new Set())
const page = ref(1)

const attendance = computed(() => kpis.value?.attendanceSummary)
const present = computed(() => attendance.value?.present ?? 0)
const absent = computed(() => attendance.value?.absent ?? 0)
const marked = computed(() => attendance.value?.total ?? 0)
const attendanceRate = computed(() => {
  if (marked.value <= 0) return null
  return Math.round((present.value / marked.value) * 1000) / 10
})

const statusTabs = computed(() => {
  const total = meta.value.total
  const active = kpis.value?.activeStudents ?? total
  return [
    { key: 'active', label: 'Active', count: statusFilter.value === 'active' ? total : active },
    { key: 'inactive', label: 'Inactive', count: statusFilter.value === 'inactive' ? total : 0 },
    { key: 'graduated', label: 'Graduated', count: statusFilter.value === 'graduated' ? total : 0 },
    { key: 'suspended', label: 'Suspended', count: statusFilter.value === 'suspended' ? total : 0 },
    { key: 'all', label: 'All', count: kpis.value?.totalStudents ?? total },
  ]
})

async function loadKpis() {
  try {
    kpis.value = await fetchKpis()
  } catch {
    kpis.value = null
  }
}

async function loadStudents() {
  loading.value = true
  error.value = null
  try {
    const params: ListQueryParams = {
      page: page.value,
      per_page: 24,
      search: searchQuery.value.trim() || undefined,
      include: 'guardians,classModel,stream,house,gradeLevel,studentCategory',
    }
    if (statusFilter.value && statusFilter.value !== 'all') {
      params.filter = { status: statusFilter.value }
    }
    const paginator = await fetchPaginatedList<Record<string, unknown>>(
      moduleEndpoints.students,
      params,
    )
    students.value = paginator.data
    meta.value = {
      current_page: paginator.current_page,
      last_page: paginator.last_page,
      total: paginator.total,
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load students')
    students.value = []
  } finally {
    loading.value = false
  }
}

async function refreshAll() {
  await Promise.all([loadKpis(), viewMode.value === 'grid' ? loadStudents() : Promise.resolve()])
  toast.success('Students refreshed')
}

function setStatus(key: string) {
  statusFilter.value = key
  page.value = 1
  void syncQuery()
  void loadStudents()
}

function setView(mode: 'grid' | 'list') {
  viewMode.value = mode
  void syncQuery()
  if (mode === 'grid') void loadStudents()
}

function syncQuery() {
  const query: Record<string, string> = {}
  if (searchQuery.value.trim()) query.search = searchQuery.value.trim()
  if (statusFilter.value && statusFilter.value !== 'active') query.status = statusFilter.value
  if (viewMode.value === 'list') query.view = 'list'
  if (route.query.create === '1') query.create = '1'
  return router.replace({ path: route.path, query })
}

function onSearchSubmit() {
  page.value = 1
  void syncQuery()
  void loadStudents()
}

function addStudent() {
  void router.push({ path: '/students/create' })
}

function exportCsv() {
  void router.push({ path: '/people', query: { tab: 'students' } })
  toast.success('Open the list view to export CSV from the toolbar')
  setView('list')
}

function printPage() {
  window.print()
}

function toggleSelected(id: string | number, value: boolean) {
  const next = new Set(selectedIds.value)
  if (value) next.add(id)
  else next.delete(id)
  selectedIds.value = next
}

function goPage(next: number) {
  if (next < 1 || next > meta.value.last_page) return
  page.value = next
  void loadStudents()
}

watch(
  () => route.query.create,
  (create) => {
    if (create === '1') setView('list')
  },
)

onMounted(() => {
  void loadKpis()
  if (viewMode.value === 'grid') void loadStudents()
  else loading.value = false
})
</script>

<template>
  <PageShell
    title="Students"
    description="Directory of enrolled learners with grid and list views."
    :breadcrumbs="['Students']"
    max-width="wide"
  >
    <template #actions>
      <Button
        variant="outline"
        size="icon"
        class="size-9 rounded-xl"
        aria-label="Refresh students"
        @click="refreshAll"
      >
        <RefreshCw class="size-4" aria-hidden="true" />
      </Button>
      <Button
        variant="outline"
        size="icon"
        class="size-9 rounded-xl"
        aria-label="Print"
        @click="printPage"
      >
        <Printer class="size-4" aria-hidden="true" />
      </Button>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="outline" class="gap-2 rounded-xl">
            <Download class="size-4" aria-hidden="true" />
            Export
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="rounded-xl">
          <DropdownMenuItem class="cursor-pointer" @click="exportCsv">
            Export CSV (list view)
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <Button class="gap-1.5 rounded-xl" @click="addStudent">
        <Plus class="size-4" aria-hidden="true" />
        Add Student
      </Button>
    </template>

    <section
      aria-labelledby="student-directory-kpis"
      class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
    >
      <h2 id="student-directory-kpis" class="sr-only">Student summary</h2>
      <KpiCard
        title="Total Students"
        :value="(kpis?.totalStudents ?? 0).toLocaleString()"
        :subtitle="`${kpis?.activeStudents ?? 0} active · ${kpis?.totalClasses ?? 0} classes`"
        :trend="kpis?.studentsGrowth"
        :icon="GraduationCap"
      />
      <KpiCard
        title="Present Today"
        :value="present.toLocaleString()"
        :subtitle="marked ? `${attendanceRate}% of ${marked.toLocaleString()} marked` : 'No marks today'"
        :icon="UserRound"
        accent="success"
      />
      <KpiCard
        title="Absent Today"
        :value="absent.toLocaleString()"
        :subtitle="`${attendance?.excused ?? 0} excused · ${attendance?.late ?? 0} late`"
        :icon="UserMinus"
        accent="danger"
      />
      <KpiCard
        title="Fee Pending"
        :value="formatMoney(kpis?.outstandingFees ?? 0, school?.currency)"
        subtitle="Outstanding balances"
        :icon="Banknote"
        accent="warning"
      />
      <KpiCard
        title="Avg. Attendance"
        :value="attendanceRate != null ? `${attendanceRate}%` : '—'"
        subtitle="Based on today's register"
        :icon="ClipboardCheck"
        accent="success"
        :empty="attendanceRate == null"
      />
      <KpiCard
        title="New Admissions"
        :value="(kpis?.newAdmissionsThisMonth ?? 0).toLocaleString()"
        subtitle="This month"
        :icon="TrendingUp"
      />
      <KpiCard
        title="Transport"
        value="—"
        subtitle="Allocations coming soon"
        :icon="Bus"
        empty
      />
      <KpiCard
        title="Hostel"
        value="—"
        subtitle="Allocations coming soon"
        :icon="BedDouble"
        empty
      />
    </section>

    <section
      class="flex flex-col gap-3 rounded-2xl bg-card p-3 shadow-[var(--shadow-soft)] ring-1 ring-border/70 dark:ring-border/50 sm:p-4"
      aria-label="Student filters"
    >
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <form class="relative min-w-0 flex-1 lg:max-w-sm" @submit.prevent="onSearchSubmit">
          <label for="student-grid-search" class="sr-only">Search students</label>
          <Search
            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
          />
          <Input
            id="student-grid-search"
            v-model="searchQuery"
            type="search"
            placeholder="Search by name or admission no…"
            class="h-10 rounded-xl border-border/80 bg-background pl-9 shadow-none"
            autocomplete="off"
          />
        </form>

        <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="Status filters">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="statusFilter === tab.key"
            class="inline-flex h-9 items-center gap-1.5 rounded-xl px-3.5 text-sm font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="cn(
              statusFilter === tab.key
                ? 'bg-primary text-primary-foreground shadow-sm'
                : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
            )"
            @click="setStatus(tab.key)"
          >
            {{ tab.label }}
            <span
              :class="cn(
                'rounded-full px-1.5 py-px text-xs tabular-nums',
                statusFilter === tab.key
                  ? 'bg-primary-foreground/20 text-primary-foreground'
                  : 'bg-background/80 text-muted-foreground',
              )"
            >{{ tab.count }}</span>
          </button>
        </div>

        <div class="flex items-center gap-1 rounded-xl bg-muted/50 p-1 ring-1 ring-border/60">
          <Button
            variant="ghost"
            size="icon"
            class="size-8 rounded-lg"
            :class="viewMode === 'grid' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'"
            aria-label="Grid view"
            :aria-pressed="viewMode === 'grid'"
            @click="setView('grid')"
          >
            <LayoutGrid class="size-4" aria-hidden="true" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="size-8 rounded-lg"
            :class="viewMode === 'list' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground'"
            aria-label="List view"
            :aria-pressed="viewMode === 'list'"
            @click="setView('list')"
          >
            <List class="size-4" aria-hidden="true" />
          </Button>
        </div>
      </div>
    </section>

    <div v-if="viewMode === 'list'">
      <RegistrySection list-key="students" :auto-create="route.query.create === '1'" />
    </div>

    <div v-else class="space-y-4">
      <PageLoader v-if="loading && !students.length" label="Loading students" />

      <div
        v-else-if="error"
        class="rounded-2xl bg-destructive/5 px-4 py-8 text-center text-sm text-destructive ring-1 ring-destructive/25"
        role="alert"
      >
        {{ error }}
        <div class="mt-3">
          <Button variant="outline" size="sm" class="rounded-xl" @click="loadStudents">
            Try again
          </Button>
        </div>
      </div>

      <EmptyState
        v-else-if="!students.length"
        title="No students found"
        description="Try another status filter or add a new learner."
      >
        <Button class="gap-1.5 rounded-xl" @click="addStudent">
          <Plus class="size-4" aria-hidden="true" />
          Add Student
        </Button>
      </EmptyState>

      <template v-else>
        <p
          v-if="selectedIds.size"
          class="text-sm text-muted-foreground"
          aria-live="polite"
        >
          {{ selectedIds.size }} selected
        </p>
        <div
          class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
          aria-label="Students grid"
        >
          <StudentGridCard
            v-for="student in students"
            :key="String(student.id)"
            :student="student"
            :currency="school?.currency"
            :selected="selectedIds.has(student.id as string | number)"
            @update:selected="toggleSelected(student.id as string | number, $event)"
          />
        </div>

        <nav
          v-if="meta.last_page > 1"
          class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-card px-4 py-3 shadow-[var(--shadow-soft)] ring-1 ring-border/60"
          aria-label="Students pagination"
        >
          <p class="text-sm text-muted-foreground">
            Page {{ meta.current_page }} of {{ meta.last_page }}
            · {{ meta.total.toLocaleString() }} students
          </p>
          <div class="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              class="rounded-xl"
              :disabled="meta.current_page <= 1 || loading"
              @click="goPage(meta.current_page - 1)"
            >
              Previous
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="rounded-xl"
              :disabled="meta.current_page >= meta.last_page || loading"
              @click="goPage(meta.current_page + 1)"
            >
              Next
            </Button>
          </div>
        </nav>
      </template>
    </div>
  </PageShell>
</template>
