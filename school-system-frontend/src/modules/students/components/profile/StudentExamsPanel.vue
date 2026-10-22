<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { ClipboardList, Download, Printer, Trophy } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { studentsApi } from '@/services/api.service'
import { formatDate } from '@/lib/format'
import { gradeLetterTone, STATUS_TONE_BADGE } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

type GradeRow = { subject?: string; score?: number; term?: string; grade?: string }

const props = defineProps<{
  studentId: string | number
  averageScore?: number | null
  grades?: GradeRow[]
  downloading?: boolean
}>()

const emit = defineEmits<{
  'download-report': []
  print: []
}>()

const loading = ref(false)
const exams = ref<Record<string, unknown>[]>([])

const letter = computed(() => {
  const score = props.averageScore
  if (score == null) return '—'
  const s = Number(score)
  if (s >= 85) return 'A'
  if (s >= 70) return 'B'
  if (s >= 55) return 'C'
  if (s >= 40) return 'D'
  return 'F'
})

const distinction = computed(() => {
  if (props.averageScore == null) return null
  const s = Number(props.averageScore)
  if (s >= 85) return 'Distinction'
  if (s >= 60) return 'Merit'
  if (s >= 40) return 'Pass'
  return 'Fail'
})

const recent = computed(() => (props.grades ?? []).slice(0, 10))

async function loadExams() {
  loading.value = true
  try {
    exams.value = (await studentsApi.exams(props.studentId)) as Record<string, unknown>[]
  } catch {
    exams.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.studentId, loadExams)
onMounted(loadExams)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-2">
        <ClipboardList class="size-4 text-primary" aria-hidden="true" />
        <h2 class="text-base font-semibold">Exams & results</h2>
      </div>
      <div class="flex flex-wrap gap-2 print:hidden">
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="rounded-xl"
          :disabled="downloading"
          :aria-busy="downloading"
          @click="emit('download-report')"
        >
          <Download class="mr-1.5 size-4" aria-hidden="true" />
          Report card
        </Button>
        <Button type="button" variant="outline" size="sm" class="rounded-xl" @click="emit('print')">
          <Printer class="mr-1.5 size-4" aria-hidden="true" />
          Print
        </Button>
      </div>
    </div>

    <Card>
      <CardContent class="grid gap-5 p-5 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-center">
        <div
          :class="cn('flex size-24 flex-col items-center justify-center rounded-2xl text-primary-foreground', 'bg-primary')"
        >
          <p class="text-4xl font-semibold leading-none">{{ letter }}</p>
          <p v-if="distinction" class="mt-2 rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-medium uppercase">
            {{ distinction }}
          </p>
        </div>
        <div class="space-y-3">
          <div class="flex flex-wrap items-end gap-3">
            <p class="text-4xl font-semibold tabular-nums tracking-tight">
              {{ averageScore != null ? `${Number(averageScore).toFixed(0)}%` : '—' }}
            </p>
            <Badge v-if="averageScore != null" :class="cn('border', STATUS_TONE_BADGE.success)">Overall score</Badge>
          </div>
          <p class="text-sm text-muted-foreground">
            Aggregate of recorded grades
            <span v-if="grades?.length"> · {{ grades.length }} entries</span>
          </p>
          <div class="space-y-1">
            <div class="flex h-2 overflow-hidden rounded-full bg-muted">
              <div class="w-[40%] bg-destructive/70" />
              <div class="w-[20%] bg-chart-3/80" />
              <div class="w-[25%] bg-chart-4/80" />
              <div class="w-[15%] bg-primary" />
            </div>
            <div class="flex justify-between text-[10px] text-muted-foreground">
              <span>Fail</span>
              <span>Pass 40</span>
              <span>Merit 60</span>
              <span>Distinction 85+</span>
            </div>
          </div>
        </div>
        <div class="grid gap-2 sm:grid-cols-3 lg:grid-cols-1">
          <div class="rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/50">
            <p class="text-lg font-semibold tabular-nums">{{ exams.length || '—' }}</p>
            <p class="text-xs text-muted-foreground">Exams linked</p>
          </div>
          <div class="rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/50">
            <p class="text-lg font-semibold tabular-nums">{{ grades?.length ?? 0 }}</p>
            <p class="text-xs text-muted-foreground">Results on file</p>
          </div>
          <div class="rounded-xl bg-muted/40 px-3 py-2 ring-1 ring-border/50">
            <p class="flex items-center gap-1 text-lg font-semibold">
              <Trophy class="size-4 text-chart-3" aria-hidden="true" />
              {{ letter }}
            </p>
            <p class="text-xs text-muted-foreground">Band</p>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Upcoming / linked exams</CardTitle>
      </CardHeader>
      <CardContent class="p-5">
        <PageLoader v-if="loading" label="Loading exams" />
        <ul v-else-if="exams.length" class="space-y-3">
          <li
            v-for="(exam, index) in exams.slice(0, 6)"
            :key="String(exam.id ?? index)"
            class="flex flex-col gap-2 rounded-2xl border border-border/70 p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex items-start gap-3">
              <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-primary/10 text-primary">
                <span class="text-sm font-semibold leading-none">
                  {{ formatDate(String(exam.exam_date ?? exam.date ?? '')).slice(0, 2) || '—' }}
                </span>
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ exam.name ?? exam.title ?? 'Exam' }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ formatDate(String(exam.exam_date ?? exam.date ?? '')) }}
                  <span v-if="exam.subject"> · {{ exam.subject }}</span>
                </p>
              </div>
            </div>
            <Badge variant="secondary" class="w-fit font-normal capitalize">
              {{ exam.status ?? 'Scheduled' }}
            </Badge>
          </li>
        </ul>
        <EmptyState
          v-else
          variant="embedded"
          title="No exams linked"
          description="Published or scheduled exams for this student will show here."
        />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Recent exam results</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <Table v-if="recent.length">
          <TableHeader>
            <TableRow>
              <TableHead>Subject</TableHead>
              <TableHead>Term</TableHead>
              <TableHead>Score</TableHead>
              <TableHead>Grade</TableHead>
              <TableHead>Result</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(row, i) in recent" :key="i">
              <TableCell class="font-medium">{{ row.subject ?? '—' }}</TableCell>
              <TableCell>{{ row.term ?? '—' }}</TableCell>
              <TableCell>
                <div class="space-y-1">
                  <span class="tabular-nums">{{ row.score ?? '—' }}</span>
                  <Progress
                    v-if="row.score != null"
                    :model-value="Math.min(100, Number(row.score))"
                    class="h-1.5 w-24"
                  />
                </div>
              </TableCell>
              <TableCell>
                <Badge
                  v-if="row.grade"
                  :class="cn('border font-semibold', STATUS_TONE_BADGE[gradeLetterTone(String(row.grade))])"
                >
                  {{ row.grade }}
                </Badge>
                <span v-else>—</span>
              </TableCell>
              <TableCell>
                <Badge
                  :class="cn(
                    'border font-normal',
                    Number(row.score ?? 0) >= 40 ? STATUS_TONE_BADGE.success : STATUS_TONE_BADGE.danger,
                  )"
                >
                  {{ Number(row.score ?? 0) >= 40 ? 'Pass' : 'Fail' }}
                </Badge>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div v-else class="p-5">
          <EmptyState variant="embedded" title="No results yet" description="Exam and grade results will list here." />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
