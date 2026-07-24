<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  BookOpen,
  ChevronRight,
  ClipboardCheck,
  NotebookPen,
  Search,
  Users,
} from '@lucide/vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

interface TeachingClass {
  class_id: number
  class_name: string
  subjects?: string[]
  student_count?: number
  students?: Array<{
    id: number
    full_name?: string
    student_number?: string
  }>
}

const route = useRoute()
const loading = ref(true)
const error = ref<string | null>(null)
const classes = ref<TeachingClass[]>([])
const selectedClassId = ref<number | null>(null)
const search = ref('')
const showLearners = ref(false)
const selectedStudent = ref<any | null>(null)
const detailLoading = ref(false)

const selectedClass = computed(() =>
  classes.value.find((c) => c.class_id === selectedClassId.value) ?? null,
)

const filteredStudents = computed(() => {
  const students = selectedClass.value?.students ?? []
  const q = search.value.trim().toLowerCase()
  if (!q) return students
  return students.filter(
    (s) =>
      String(s.full_name ?? '').toLowerCase().includes(q) ||
      String(s.student_number ?? '').toLowerCase().includes(q),
  )
})

const classActions = computed(() => {
  const id = selectedClassId.value
  if (!id) return []
  return [
    {
      label: 'Mark attendance',
      description: 'Take today’s register',
      href: { path: '/academics/attendance', query: { class_id: String(id) } },
      icon: ClipboardCheck,
      primary: true,
    },
    {
      label: 'Enter marks',
      description: 'Open gradebook',
      href: { path: '/academics/grades', query: { class_id: String(id) } },
      icon: NotebookPen,
      primary: false,
    },
    {
      label: 'Homework',
      description: 'Submissions to grade',
      href: { path: '/teaching', query: { tab: 'homework' } },
      icon: BookOpen,
      primary: false,
    },
    {
      label: 'Lesson plans',
      description: 'Plan this class',
      href: { path: '/teaching', query: { tab: 'lessons' } },
      icon: NotebookPen,
      primary: false,
    },
  ]
})

async function load() {
  loading.value = true
  error.value = null
  try {
    classes.value = (await teacherPortalApi.classes()) as TeachingClass[]
    const fromQuery = Number(route.query.class_id)
    if (fromQuery && classes.value.some((c) => c.class_id === fromQuery)) {
      selectedClassId.value = fromQuery
    } else if (classes.value.length && selectedClassId.value == null) {
      selectedClassId.value = classes.value[0].class_id
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load classes')
  } finally {
    loading.value = false
  }
}

function selectClass(id: number) {
  if (selectedClassId.value === id) return
  selectedClassId.value = id
  selectedStudent.value = null
  showLearners.value = false
  search.value = ''
}

async function openStudent(id: number) {
  detailLoading.value = true
  try {
    selectedStudent.value = await teacherPortalApi.student(id)
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not load student'))
  } finally {
    detailLoading.value = false
  }
}

watch(selectedClassId, () => {
  selectedStudent.value = null
})

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-1">
      <h2 class="font-heading text-xl font-semibold tracking-tight text-foreground">
        Your classes
      </h2>
      <p class="max-w-xl text-sm text-muted-foreground">
        Select a class, then do the next job — register, marks, homework, or plans.
      </p>
    </header>

    <PageLoader v-if="loading" label="Loading your classes…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <p
        v-if="!classes.length"
        class="rounded-2xl border border-dashed border-border/70 px-6 py-12 text-center text-sm text-muted-foreground"
        role="status"
      >
        No classes assigned yet. Ask an administrator to link your teaching assignments.
      </p>

      <div v-else class="grid gap-6 lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)] xl:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
        <!-- Class picker -->
        <section aria-label="Your classes" class="space-y-2">
          <p class="px-1 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
            Classes
          </p>
          <ul class="space-y-1.5" role="listbox" aria-label="Assigned classes">
            <li v-for="cls in classes" :key="cls.class_id">
              <button
                type="button"
                role="option"
                :aria-selected="selectedClassId === cls.class_id"
                :class="cn(
                  'flex w-full items-start gap-3 rounded-xl border px-3 py-3 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                  selectedClassId === cls.class_id
                    ? 'border-primary/30 bg-primary/5 shadow-sm'
                    : 'border-border/60 bg-background hover:bg-muted/40',
                )"
                @click="selectClass(cls.class_id)"
              >
                <span
                  :class="cn(
                    'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg',
                    selectedClassId === cls.class_id
                      ? 'bg-primary/15 text-primary'
                      : 'bg-muted text-muted-foreground',
                  )"
                  aria-hidden="true"
                >
                  <Users class="size-4" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-semibold text-foreground">
                    {{ cls.class_name }}
                  </span>
                  <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                    {{ (cls.subjects || []).join(', ') || 'No subject' }}
                    · {{ cls.student_count ?? cls.students?.length ?? 0 }} learners
                  </span>
                </span>
                <ChevronRight
                  class="mt-1 size-4 shrink-0 text-muted-foreground"
                  aria-hidden="true"
                />
              </button>
            </li>
          </ul>
        </section>

        <!-- Selected class workspace -->
        <section
          v-if="selectedClass"
          class="space-y-6"
          :aria-label="`${selectedClass.class_name} actions`"
        >
          <div class="space-y-1 border-b border-border/50 pb-4">
            <h3 class="font-heading text-lg font-semibold text-foreground">
              {{ selectedClass.class_name }}
            </h3>
            <p class="text-sm text-muted-foreground">
              {{ (selectedClass.subjects || []).join(', ') || 'No subject' }}
              · {{ selectedClass.student_count ?? selectedClass.students?.length ?? 0 }} learners
            </p>
          </div>

          <div>
            <p class="mb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
              What do you need to do?
            </p>
            <div class="grid gap-3 sm:grid-cols-2">
              <Button
                v-for="action in classActions"
                :key="action.label"
                as-child
                :variant="action.primary ? 'default' : 'outline'"
                :class="cn(
                  'h-auto justify-start gap-3 px-4 py-3 text-left whitespace-normal',
                  action.primary && 'shadow-sm',
                )"
              >
                <RouterLink
                  :to="action.href"
                  active-class=""
                  exact-active-class=""
                >
                  <span
                    :class="cn(
                      'flex size-9 shrink-0 items-center justify-center rounded-lg',
                      action.primary ? 'bg-primary-foreground/15' : 'bg-muted',
                    )"
                    aria-hidden="true"
                  >
                    <component :is="action.icon" class="size-4" />
                  </span>
                  <span class="min-w-0">
                    <span class="block text-sm font-semibold">{{ action.label }}</span>
                    <span
                      :class="cn(
                        'block text-xs font-normal',
                        action.primary ? 'text-primary-foreground/80' : 'text-muted-foreground',
                      )"
                    >
                      {{ action.description }}
                    </span>
                  </span>
                </RouterLink>
              </Button>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h4 class="text-sm font-semibold text-foreground">Learners</h4>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                class="h-8"
                :aria-expanded="showLearners"
                @click="showLearners = !showLearners"
              >
                {{ showLearners ? 'Hide list' : 'Show list' }}
              </Button>
            </div>

            <template v-if="showLearners">
              <div class="space-y-2">
                <Label for="learner-search" class="sr-only">Search learners</Label>
                <div class="relative">
                  <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Input
                    id="learner-search"
                    v-model="search"
                    class="pl-9"
                    placeholder="Search by name or student number"
                  />
                </div>
              </div>

              <ul class="divide-y divide-border/60 overflow-hidden rounded-xl border border-border/60">
                <li v-for="s in filteredStudents" :key="s.id">
                  <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
                    @click="openStudent(s.id)"
                  >
                    <span class="font-medium text-foreground">{{ s.full_name }}</span>
                    <span class="shrink-0 text-muted-foreground">{{ s.student_number }}</span>
                  </button>
                </li>
                <li
                  v-if="!filteredStudents.length"
                  class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                  No matching learners.
                </li>
              </ul>
            </template>
          </div>

          <aside
            v-if="selectedStudent || detailLoading"
            class="rounded-2xl border border-border/60 bg-muted/15 p-4"
            aria-live="polite"
          >
            <p v-if="detailLoading" class="text-sm text-muted-foreground">Loading learner…</p>
            <template v-else-if="selectedStudent">
              <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                <div>
                  <p class="text-base font-semibold text-foreground">
                    {{ selectedStudent.student?.full_name }}
                  </p>
                  <p class="text-sm text-muted-foreground">
                    Attendance, behaviour, and contacts
                  </p>
                </div>
                <Button type="button" variant="ghost" size="sm" @click="selectedStudent = null">
                  Close
                </Button>
              </div>
              <div class="grid gap-4 md:grid-cols-3">
                <div>
                  <p class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Attendance
                  </p>
                  <ul class="space-y-1.5 text-sm">
                    <li v-for="a in selectedStudent.attendance_history || []" :key="a.id">
                      {{ a.date }} —
                      <Badge variant="outline" class="capitalize">{{ a.status }}</Badge>
                    </li>
                    <li
                      v-if="!(selectedStudent.attendance_history || []).length"
                      class="text-muted-foreground"
                    >
                      No records.
                    </li>
                  </ul>
                </div>
                <div>
                  <p class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Behaviour
                  </p>
                  <ul class="space-y-1.5 text-sm">
                    <li v-for="b in selectedStudent.behaviour_history || []" :key="b.id">
                      {{ b.recorded_on }} · {{ b.points }} pts · {{ b.category }}
                    </li>
                    <li
                      v-if="!(selectedStudent.behaviour_history || []).length"
                      class="text-muted-foreground"
                    >
                      No records.
                    </li>
                  </ul>
                </div>
                <div>
                  <p class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    Parent contact
                  </p>
                  <p class="text-sm">{{ selectedStudent.parent_contacts?.phone || '—' }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ selectedStudent.parent_contacts?.email || '—' }}
                  </p>
                </div>
              </div>
            </template>
          </aside>
        </section>
      </div>
    </template>
  </div>
</template>
