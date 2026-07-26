<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  Eye,
  GraduationCap,
  NotebookPen,
  Pencil,
  Users,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DashboardHero from '@/components/dashboard/DashboardHero.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import type { MetricCard } from '@/components/dashboard/MetricBand.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAuth } from '@/composables/useAuth'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { getErrorMessage, unwrapList } from '@/lib/api-response'
import { formatDate, formatTime } from '@/lib/format'
import { cn } from '@/lib/utils'
import {
  assignmentTypeTone,
  gradeLetterTone,
  lessonStatusTone,
  STATUS_TONE_BADGE,
} from '@/lib/ui-status'
import { canShowDashboardItem } from '@/lib/dashboard-access'
import { getRoleDashboardMeta } from '@/lib/role-dashboard'
import { useRouter } from 'vue-router'
import { academicsApi, teachersApi, teacherPortalApi } from '@/services/api.service'
import { fetchList } from '@/services/dashboard.service'
import { moduleEndpoints } from '@/services'
import { api } from '@/lib/api'
import { endpoints } from '@/services/endpoints'

interface TeacherProfile {
  id?: number
  name?: string
  subject?: string
  department?: string
  title?: string
}

interface AssignmentRow {
  id: number
  class_id?: number | null
  subject_id?: number | null
  is_active?: boolean
  class_model?: { id?: number; name?: string } | null
  subject?: { id?: number; name?: string } | null
}

interface TimetableSlot {
  id: number
  day?: string
  start_time?: string
  end_time?: string
  subject?: string | { name?: string }
  room?: string | { name?: string } | null
  class_model?: { id?: number; name?: string } | null
  class_id?: number
}

interface ClassCard {
  classId: number
  className: string
  subject: string
  studentCount: number
  nextLesson: string | null
}

interface LessonRow {
  id: number
  start: string
  end: string
  subject: string
  className: string
  room: string
  status: 'completed' | 'current' | 'upcoming'
}

interface GradeRow {
  id: number
  student?: string
  assignment?: string
  className?: string
  percent?: number | null
  letter?: string | null
}

interface WorkRow {
  id: number
  title: string
  type: 'assignment' | 'test' | 'project'
  className: string
  dueDate?: string | null
  submissionsLabel: string
  totalMarks?: number | string | null
  href: string
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as const

const { user } = useAuth()
const router = useRouter()
const { school, loadSchool } = useSchoolProfile()
const meta = getRoleDashboardMeta(user.value?.role)

const loading = ref(true)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const teacher = ref<TeacherProfile | null>(null)
const classCards = ref<ClassCard[]>([])
const todayLessons = ref<LessonRow[]>([])
const recentGrades = ref<GradeRow[]>([])
const workItems = ref<WorkRow[]>([])
const pendingGrades = ref(0)
const portalExtras = ref<{
  pending_attendance_classes?: number
  upcoming_exams?: Array<{ id: number; name?: string; exam_date?: string }>
  announcements?: Array<{ id: number; title?: string; created_at?: string }>
  unread_notifications?: number
  workload?: { lesson_plans_draft?: number; assignments_open?: number; submissions_to_grade?: number }
} | null>(null)

const heroName = computed(() => teacher.value?.name || user.value?.name || 'Teacher')

const subjectLine = computed(() => {
  const subject = teacher.value?.subject || teacher.value?.department || 'Teacher'
  const schoolName = school.value?.name || 'Your school'
  return `${subject} Teacher · ${schoolName}`
})

const heroSubtitle = computed(() => `${subjectLine.value}. ${meta.subtitle}`)

const totalStudents = computed(() =>
  classCards.value.reduce((sum, c) => sum + c.studentCount, 0),
)

const overviewCards = computed<MetricCard[]>(() => [
  {
    title: 'My Classes',
    value: classCards.value.length,
    subtitle: 'Open in Teaching',
    icon: BookOpen,
    href: '/teaching?tab=classes',
    capability: 'isStaff',
  },
  {
    title: 'Total Students',
    value: totalStudents.value,
    subtitle: 'Across your classes',
    icon: Users,
    href: '/teaching?tab=classes',
    capability: 'canManageStudents',
  },
  {
    title: "Today's Lessons",
    value: todayLessons.value.length,
    subtitle: 'On your timetable today',
    icon: CalendarDays,
    href: '/academics/my-timetable',
    capability: 'isStaff',
  },
  {
    title: 'Pending Grades',
    value: pendingGrades.value,
    subtitle: 'Tests needing attention',
    icon: GraduationCap,
    href: '/academics/grades',
    capability: ['canManageExaminations', 'canEnterExamResults'],
    accent: pendingGrades.value > 0 ? 'warning' : undefined,
  },
  {
    title: 'Pending attendance',
    value: portalExtras.value?.pending_attendance_classes ?? 0,
    subtitle: 'Classes without today’s register',
    icon: ClipboardCheck,
    href: '/academics/attendance',
    capability: 'canManageStudents',
    accent: (portalExtras.value?.pending_attendance_classes ?? 0) > 0 ? 'warning' : undefined,
  },
])

const quickActions = computed(() => {
  const actions = [
    {
      label: 'Take attendance',
      href: '/academics/attendance',
      icon: ClipboardCheck,
      primary: true,
      capability: 'canManageStudents' as const,
    },
    {
      label: 'Open Teaching',
      href: '/teaching',
      icon: BookOpen,
      primary: false,
      capability: 'isStaff' as const,
    },
    {
      label: 'Enter marks',
      href: '/academics/grades',
      icon: NotebookPen,
      primary: false,
      capability: 'canEnterExamResults' as const,
    },
  ]
  return actions.filter((action) =>
    canShowDashboardItem(
      user.value,
      { href: action.href, capability: action.capability },
      router,
    ),
  )
})

const canOpenClasses = computed(() =>
  canShowDashboardItem(user.value, { href: '/teaching?tab=classes', capability: 'isStaff' }, router),
)

const canTakeAttendance = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/attendance', capability: 'canManageStudents' },
    router,
  ),
)

const canOpenGradebook = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/grades', capability: ['canManageExaminations', 'canEnterExamResults'] },
    router,
  ),
)

const canOpenExams = computed(() =>
  canShowDashboardItem(
    user.value,
    { href: '/academics/exams', capability: ['canManageExaminations', 'canEnterExamResults'] },
    router,
  ),
)

/** Row actions link to capability-gated list pages — hide them when the target would be denied. */
function canOpenWorkItem(href: string) {
  return canShowDashboardItem(user.value, { href, allowWithoutCapability: true }, router)
}

function clock(value?: string | null) {
  return formatTime(value, '—')
}

function subjectLabel(slot: TimetableSlot) {
  if (typeof slot.subject === 'string' && slot.subject.trim()) return slot.subject
  if (slot.subject && typeof slot.subject === 'object' && slot.subject.name) return slot.subject.name
  return 'Lesson'
}

function roomLabel(slot: TimetableSlot) {
  if (typeof slot.room === 'string' && slot.room.trim()) return slot.room
  if (slot.room && typeof slot.room === 'object' && slot.room.name) return slot.room.name
  return '—'
}

function minutesNow() {
  const d = new Date()
  return d.getHours() * 60 + d.getMinutes()
}

function toMinutes(hhmm: string) {
  const [h, m] = hhmm.split(':').map(Number)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null
  return h * 60 + m
}

function lessonStatus(start: string, end: string): LessonRow['status'] {
  const now = minutesNow()
  const s = toMinutes(start)
  const e = toMinutes(end)
  if (s == null || e == null) return 'upcoming'
  if (now >= e) return 'completed'
  if (now >= s && now < e) return 'current'
  return 'upcoming'
}

function gradeBadgeClass(letter?: string | null) {
  return STATUS_TONE_BADGE[gradeLetterTone(String(letter ?? ''))]
}

function workTypeBadge(type: WorkRow['type']) {
  return STATUS_TONE_BADGE[assignmentTypeTone(type)]
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await loadSchool()
    const teacherId = user.value?.teacher_id

    if (teacherId) {
      try {
        teacher.value = (await teachersApi.get(teacherId)) as TeacherProfile
      } catch {
        teacher.value = { name: user.value?.name }
      }
    } else {
      teacher.value = { name: user.value?.name }
    }

    const [assignments, timetableRes, assignmentList, testList, portal] = await Promise.all([
      teacherId
        ? (academicsApi.teacherAssignments.list({
            teacher_id: teacherId,
          }) as Promise<AssignmentRow[]>).catch(() => [] as AssignmentRow[])
        : Promise.resolve([] as AssignmentRow[]),
      api.get(endpoints.timetable.list).then((r) => unwrapList<TimetableSlot>(r.data)).catch(() => [] as TimetableSlot[]),
      teacherId
        ? (academicsApi.assignments.list({ teacher_id: teacherId, limit: 20 }) as Promise<Record<string, unknown>[]>).catch(() => [])
        : Promise.resolve([]),
      teacherId
        ? (academicsApi.tests.list({ teacher_id: teacherId, limit: 20 }) as Promise<Record<string, unknown>[]>).catch(() => [])
        : Promise.resolve([]),
      teacherPortalApi.dashboard().catch(() => null),
    ])
    portalExtras.value = portal as typeof portalExtras.value

    const activeAssignments = assignments.filter((a) => a.is_active !== false && a.class_id)
    const classMap = new Map<number, ClassCard>()

    for (const a of activeAssignments) {
      const classId = Number(a.class_id)
      if (!Number.isFinite(classId)) continue
      const existing = classMap.get(classId)
      const className = a.class_model?.name || `Class ${classId}`
      const subject = a.subject?.name || teacher.value?.subject || 'Subject'
      if (!existing) {
        classMap.set(classId, {
          classId,
          className,
          subject,
          studentCount: 0,
          nextLesson: null,
        })
      }
    }

    // Only use timetable rows for classes this teacher is assigned to.
    const assignedClassIds = new Set(classMap.keys())
    const myTimetable = timetableRes.filter((slot) => {
      const classId = Number(slot.class_id ?? slot.class_model?.id)
      return Number.isFinite(classId) && assignedClassIds.has(classId)
    })

    const classIds = [...classMap.keys()]
    await Promise.all(
      classIds.map(async (classId) => {
        try {
          const students = await fetchList(moduleEndpoints.students, {
            filter: { class_id: classId },
            limit: 500,
          })
          const card = classMap.get(classId)
          if (card) card.studentCount = students.length
        } catch {
          /* keep 0 */
        }
      }),
    )

    const todayName = DAYS[new Date().getDay()]
    const todaySlots = myTimetable
      .filter((s) => (s.day || '') === todayName)
      .map((s) => {
        const start = clock(s.start_time)
        const end = clock(s.end_time)
        return {
          id: s.id,
          start,
          end,
          subject: subjectLabel(s),
          className: s.class_model?.name || '—',
          room: roomLabel(s),
          status: start !== '—' && end !== '—' ? lessonStatus(start, end) : ('upcoming' as const),
          classId: Number(s.class_id ?? s.class_model?.id ?? 0),
          sortKey: toMinutes(start) ?? 9999,
        }
      })
      .sort((a, b) => a.sortKey - b.sortKey)

    todayLessons.value = todaySlots.map(({ sortKey: _s, classId: _c, ...row }) => row)

    for (const card of classMap.values()) {
      const next = todaySlots.find(
        (l) => l.classId === card.classId && (l.status === 'current' || l.status === 'upcoming'),
      )
      card.nextLesson = next ? `${next.start} – ${next.end}` : null
    }

    classCards.value = [...classMap.values()].sort((a, b) => a.className.localeCompare(b.className))

    // Recent grades from first few classes
    const gradeRows: GradeRow[] = []
    for (const classId of classIds.slice(0, 3)) {
      try {
        const grades = (await academicsApi.grades.byClass(classId)) as Array<Record<string, unknown>>
        for (const g of grades.slice(0, 8)) {
          const score = Number(g.score ?? 0)
          const total = Number(g.total ?? 0)
          const percent = total > 0 ? Math.round((score / total) * 100) : null
          gradeRows.push({
            id: Number(g.id ?? gradeRows.length),
            student: String((g.student as { full_name?: string } | undefined)?.full_name ?? 'Student'),
            assignment: String(g.subject ?? g.assessment_type ?? 'Assessment'),
            className: classMap.get(classId)?.className ?? '—',
            percent,
            letter: g.grade != null ? String(g.grade) : null,
          })
        }
      } catch {
        /* skip */
      }
    }
    recentGrades.value = gradeRows.slice(0, 8)

    const work: WorkRow[] = []
    for (const a of assignmentList.slice(0, 10)) {
      work.push({
        id: Number(a.id),
        title: String(a.title ?? a.name ?? 'Assignment'),
        type: String(a.type ?? '').toLowerCase().includes('project') ? 'project' : 'assignment',
        className: String((a.class_model as { name?: string } | undefined)?.name ?? a.class_name ?? '—'),
        dueDate: a.due_date != null ? String(a.due_date) : null,
        submissionsLabel: a.submissions_count != null
          ? `${a.submissions_count}/${a.total_students ?? '—'}`
          : '—',
        totalMarks: (a.total_marks as number | string | null | undefined) ?? (a.max_score as number | string | null | undefined) ?? '—',
        href: '/teaching?tab=homework',
      })
    }
    for (const t of testList.slice(0, 10)) {
      work.push({
        id: Number(t.id) + 100000,
        title: String(t.title ?? t.name ?? 'Test'),
        type: 'test',
        className: String((t.class_model as { name?: string } | undefined)?.name ?? t.class_name ?? '—'),
        dueDate: t.date != null ? String(t.date) : (t.due_date != null ? String(t.due_date) : null),
        submissionsLabel: t.results_count != null
          ? `${t.results_count}/${t.total_students ?? '—'}`
          : '—',
        totalMarks: (t.total_marks as number | string | null | undefined) ?? (t.max_score as number | string | null | undefined) ?? '—',
        href: '/academics/exams',
      })
    }
    pendingGrades.value = work.filter((w) => w.type === 'test').length
    workItems.value = work.slice(0, 8)
    lastUpdated.value = new Date()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load teacher dashboard')
  } finally {
    loading.value = false
  }
}

function refresh() {
  return load()
}

onMounted(load)
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] space-y-8 pb-8">
    <DashboardHero
      :name="heroName"
      :role="meta.label"
      :subtitle="heroSubtitle"
      :loading="loading"
      :last-updated="lastUpdated"
      @refresh="refresh"
    />

    <PageLoader v-if="loading" label="Loading your dashboard…" />
    <ErrorState v-else-if="error" :description="error" @retry="refresh" />

    <template v-else>
      <MetricBand
        title="Today at a glance"
        description="Classes, register, and marks that need attention"
        :cards="overviewCards"
      />

      <section v-if="quickActions.length" aria-labelledby="teacher-quick-actions">
        <h2 id="teacher-quick-actions" class="mb-3 text-sm font-medium text-muted-foreground">
          Start here
        </h2>
        <div class="grid gap-3 sm:grid-cols-3">
          <RouterLink
            v-for="action in quickActions"
            :key="action.label"
            :to="action.href"
            :class="cn(
              'flex flex-col items-start gap-3 rounded-xl border px-4 py-4 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
              action.primary
                ? 'border-foreground bg-foreground text-background hover:bg-foreground/90'
                : 'border-border/70 bg-card text-foreground hover:bg-muted/40',
            )"
          >
            <component
              :is="action.icon"
              class="size-5"
              :class="action.primary ? 'text-background' : 'text-muted-foreground'"
              aria-hidden="true"
            />
            <span class="text-sm font-semibold">{{ action.label }}</span>
          </RouterLink>
        </div>
      </section>

      <section
        v-if="portalExtras"
        class="grid gap-4 lg:grid-cols-3"
        aria-label="Announcements, exams, and workload"
      >
        <Card class="border-border/70 shadow-sm">
          <CardHeader>
            <CardTitle class="text-base">School announcements</CardTitle>
            <CardDescription>Latest notices for staff.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p v-if="!(portalExtras.announcements || []).length" class="text-muted-foreground">No announcements.</p>
            <div v-for="a in portalExtras.announcements || []" :key="a.id" class="rounded-lg border border-border/60 px-3 py-2">
              <p class="font-medium">{{ a.title }}</p>
            </div>
            <Button variant="outline" size="sm" as-child class="mt-2">
              <RouterLink to="/teaching?tab=notifications">
                Notifications ({{ portalExtras.unread_notifications ?? 0 }} unread)
              </RouterLink>
            </Button>
          </CardContent>
        </Card>
        <Card class="border-border/70 shadow-sm">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Upcoming examinations</CardTitle>
            </div>
            <Button v-if="canOpenExams" variant="outline" size="sm" as-child>
              <RouterLink to="/academics/exams">Exams</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p v-if="!(portalExtras.upcoming_exams || []).length" class="text-muted-foreground">No upcoming exams.</p>
            <div v-for="e in portalExtras.upcoming_exams || []" :key="e.id" class="rounded-lg border border-border/60 px-3 py-2">
              <p class="font-medium">{{ e.name }}</p>
              <p class="text-muted-foreground">{{ e.exam_date }}</p>
            </div>
          </CardContent>
        </Card>
        <Card class="border-border/70 shadow-sm">
          <CardHeader>
            <CardTitle class="text-base">Teaching workload</CardTitle>
            <CardDescription>Plans and homework waiting on you.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p>
              <RouterLink class="text-primary underline-offset-4 hover:underline" to="/teaching?tab=lessons">
                Draft lesson plans: {{ portalExtras.workload?.lesson_plans_draft ?? 0 }}
              </RouterLink>
            </p>
            <p>
              <RouterLink class="text-primary underline-offset-4 hover:underline" to="/teaching?tab=homework">
                Open assignments: {{ portalExtras.workload?.assignments_open ?? 0 }}
              </RouterLink>
            </p>
            <p>
              <RouterLink class="text-primary underline-offset-4 hover:underline" to="/teaching?tab=homework">
                Submissions to grade: {{ portalExtras.workload?.submissions_to_grade ?? 0 }}
              </RouterLink>
            </p>
            <Button size="sm" as-child class="mt-2">
              <RouterLink to="/teaching">Open Teaching</RouterLink>
            </Button>
          </CardContent>
        </Card>
      </section>

      <section class="grid gap-6 lg:grid-cols-2" aria-label="Classes and lessons">
        <Card class="border-border/70 shadow-sm">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">My Classes</CardTitle>
              <CardDescription>Classes you teach — open Teaching or mark the register.</CardDescription>
            </div>
            <Button v-if="canOpenClasses" variant="outline" size="sm" as-child>
              <RouterLink to="/teaching?tab=classes">All classes</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-3">
            <p v-if="!classCards.length" class="py-6 text-center text-sm text-muted-foreground">
              No class assignments yet. Ask admin to assign you to classes and subjects.
            </p>
            <div
              v-for="cls in classCards"
              :key="cls.classId"
              class="flex flex-col gap-3 rounded-xl border border-border/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <p class="font-semibold text-foreground">{{ cls.className }}</p>
                <p class="text-sm text-muted-foreground">
                  {{ cls.subject }} · {{ cls.studentCount }} students
                </p>
                <p v-if="cls.nextLesson" class="mt-1 text-xs text-muted-foreground">
                  Next lesson {{ cls.nextLesson }}
                </p>
              </div>
              <div v-if="canOpenClasses || canTakeAttendance" class="flex shrink-0 gap-2">
                <Button v-if="canOpenClasses" variant="outline" size="sm" as-child>
                  <RouterLink :to="`/teaching?tab=classes`" :aria-label="`Open ${cls.className} in Teaching`">
                    <Eye class="mr-1.5 size-3.5" aria-hidden="true" />
                    Open
                  </RouterLink>
                </Button>
                <Button v-if="canTakeAttendance" size="sm" as-child>
                  <RouterLink
                    :to="`/academics/attendance?class_id=${cls.classId}`"
                    :aria-label="`Mark register for ${cls.className}`"
                  >
                    <ClipboardCheck class="mr-1.5 size-3.5" aria-hidden="true" />
                    Mark register
                  </RouterLink>
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70 shadow-sm">
          <CardHeader class="flex flex-row items-start justify-between gap-3">
            <div>
              <CardTitle class="text-base">Today's Lessons</CardTitle>
              <CardDescription>Your timetable for today.</CardDescription>
            </div>
            <Button variant="outline" size="sm" as-child>
              <RouterLink to="/academics/my-timetable">Full timetable</RouterLink>
            </Button>
          </CardHeader>
          <CardContent class="space-y-3">
            <p v-if="!todayLessons.length" class="py-6 text-center text-sm text-muted-foreground">
              No lessons scheduled for today.
            </p>
            <div
              v-for="lesson in todayLessons"
              :key="lesson.id"
              class="flex items-start justify-between gap-3 rounded-xl border border-border/60 px-4 py-3"
            >
              <div class="min-w-0">
                <p class="text-sm font-semibold tabular-nums text-foreground">
                  {{ lesson.start }} – {{ lesson.end }}
                </p>
                <p class="truncate text-sm text-muted-foreground">
                  {{ lesson.subject }}, {{ lesson.className }} · Room {{ lesson.room }}
                </p>
              </div>
              <Badge
                variant="outline"
                :class="cn(
                  'capitalize shrink-0',
                  STATUS_TONE_BADGE[lessonStatusTone(lesson.status)],
                )"
              >
                {{ lesson.status }}
              </Badge>
            </div>
          </CardContent>
        </Card>
      </section>

      <Card class="overflow-hidden border-border/70 shadow-sm">
        <CardHeader class="flex flex-row items-center justify-between gap-3 border-b border-border/60">
          <div>
            <CardTitle class="text-base">Recent Grades</CardTitle>
            <CardDescription>Latest marks from your classes.</CardDescription>
          </div>
          <Button
            v-if="canOpenGradebook"
            as-child
          >
            <RouterLink to="/academics/grades">
              <NotebookPen class="mr-2 size-4" aria-hidden="true" />
              Add Grades
            </RouterLink>
          </Button>
        </CardHeader>
        <CardContent class="p-0">
          <p v-if="!recentGrades.length" class="py-10 text-center text-sm text-muted-foreground">
            No recent grades yet. Open the gradebook to enter marks.
          </p>
          <Table v-else>
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Assignment</TableHead>
                <TableHead>Class</TableHead>
                <TableHead>Marks</TableHead>
                <TableHead>Grade</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in recentGrades" :key="row.id">
                <TableCell class="font-medium">{{ row.student }}</TableCell>
                <TableCell>{{ row.assignment }}</TableCell>
                <TableCell>{{ row.className }}</TableCell>
                <TableCell class="tabular-nums">
                  {{ row.percent != null ? `${row.percent}%` : '—' }}
                </TableCell>
                <TableCell>
                  <Badge
                    v-if="row.letter"
                    variant="outline"
                    :class="gradeBadgeClass(row.letter)"
                  >
                    {{ row.letter }}
                  </Badge>
                  <span v-else class="text-muted-foreground">—</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card class="overflow-hidden border-border/70 shadow-sm">
        <CardHeader class="flex flex-col gap-3 border-b border-border/60 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <CardTitle class="text-base">My Assignments &amp; Tests</CardTitle>
            <CardDescription>Work set for your classes.</CardDescription>
          </div>
          <div v-if="canOpenGradebook || canOpenExams" class="flex flex-wrap gap-2">
            <Button variant="outline" as-child>
              <RouterLink to="/teaching?tab=homework">Open homework</RouterLink>
            </Button>
            <Button v-if="canOpenGradebook" variant="outline" as-child>
              <RouterLink to="/academics/grades">Open gradebook</RouterLink>
            </Button>
            <Button v-if="canOpenExams" as-child>
              <RouterLink to="/academics/exams">Open exams</RouterLink>
            </Button>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <p v-if="!workItems.length" class="py-10 text-center text-sm text-muted-foreground">
            No assignments or tests linked to you yet.
          </p>
          <div v-else class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Class</TableHead>
                  <TableHead>Due Date</TableHead>
                  <TableHead>Submissions</TableHead>
                  <TableHead>Total Marks</TableHead>
                  <TableHead class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="row in workItems" :key="row.id">
                  <TableCell class="font-medium">{{ row.title }}</TableCell>
                  <TableCell>
                    <Badge variant="outline" :class="cn('capitalize', workTypeBadge(row.type))">
                      {{ row.type }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ row.className }}</TableCell>
                  <TableCell>{{ row.dueDate ? formatDate(row.dueDate) : '—' }}</TableCell>
                  <TableCell class="tabular-nums">{{ row.submissionsLabel }}</TableCell>
                  <TableCell class="tabular-nums">{{ row.totalMarks ?? '—' }}</TableCell>
                  <TableCell class="text-right">
                    <div class="inline-flex gap-1">
                      <template v-if="canOpenWorkItem(row.href)">
                        <Button variant="ghost" size="icon" class="size-8" as-child>
                          <RouterLink :to="row.href" :aria-label="`View ${row.title}`">
                            <Eye class="size-4" aria-hidden="true" />
                          </RouterLink>
                        </Button>
                        <Button variant="ghost" size="icon" class="size-8" as-child>
                          <RouterLink :to="row.href" :aria-label="`Edit ${row.title}`">
                            <Pencil class="size-4" aria-hidden="true" />
                          </RouterLink>
                        </Button>
                      </template>
                      <span v-else class="text-sm text-muted-foreground">—</span>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
