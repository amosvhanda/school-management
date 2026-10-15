<script setup lang="ts">
import { computed } from 'vue'
import { BookOpen, FlaskConical, GraduationCap, Star, TrendingDown, TrendingUp } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
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
import EmptyState from '@/components/feedback/EmptyState.vue'
import { cn } from '@/lib/utils'
import { STATUS_TONE_BADGE, STATUS_TONE_ICON, gradeLetterTone } from '@/lib/ui-status'

type GradeRow = {
  subject?: string
  score?: number
  term?: string
  grade?: string
}

const props = defineProps<{
  averageScore?: number | null
  totalGrades?: number
  grades?: GradeRow[]
}>()

const subjectRows = computed(() => {
  const map = new Map<string, { scores: number[]; grade?: string }>()
  for (const row of props.grades ?? []) {
    const subject = String(row.subject ?? '').trim() || 'Subject'
    const entry = map.get(subject) ?? { scores: [], grade: row.grade }
    if (row.score != null && !Number.isNaN(Number(row.score))) entry.scores.push(Number(row.score))
    if (row.grade) entry.grade = String(row.grade)
    map.set(subject, entry)
  }
  return [...map.entries()]
    .map(([subject, data]) => {
      const avg = data.scores.length
        ? data.scores.reduce((a, b) => a + b, 0) / data.scores.length
        : 0
      return {
        subject,
        percent: Math.round(avg),
        grade: data.grade ?? letterFromScore(avg),
      }
    })
    .sort((a, b) => b.percent - a.percent)
})

const gpaDisplay = computed(() => {
  if (props.averageScore == null) return '—'
  // Present as /10 scale for PreSkool-style GPA card when score looks like %.
  const raw = Number(props.averageScore)
  if (raw > 10) return `${(raw / 10).toFixed(1)}/10`
  return `${raw.toFixed(1)}/10`
})

const letterBadge = computed(() => {
  if (props.averageScore == null) return '—'
  return letterFromScore(Number(props.averageScore))
})

const topSubjects = computed(() => subjectRows.value.filter((r) => r.percent >= 80).slice(0, 3))
const focusSubjects = computed(() =>
  [...subjectRows.value].filter((r) => r.percent > 0 && r.percent < 75).slice(-3).reverse(),
)

const recentGrades = computed(() => (props.grades ?? []).slice(0, 8))

function letterFromScore(score: number) {
  if (score >= 90) return 'A+'
  if (score >= 80) return 'A'
  if (score >= 70) return 'B+'
  if (score >= 60) return 'B'
  if (score >= 50) return 'C'
  if (score >= 40) return 'D'
  return 'F'
}

function subjectIcon(name: string) {
  const n = name.toLowerCase()
  if (n.includes('math')) return BookOpen
  if (n.includes('sci') || n.includes('chem') || n.includes('bio') || n.includes('phys')) return FlaskConical
  return GraduationCap
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-3">
      <Card class="overflow-hidden">
        <CardContent class="space-y-3 p-5">
          <div class="flex items-start justify-between gap-3">
            <div
              :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.accent)"
              aria-hidden="true"
            >
              <GraduationCap class="size-5" />
            </div>
            <Badge :class="STATUS_TONE_BADGE[gradeLetterTone(letterBadge)]" class="border font-semibold">
              Grade {{ letterBadge }}
            </Badge>
          </div>
          <div>
            <p class="text-3xl font-semibold tracking-tight tabular-nums">{{ gpaDisplay }}</p>
            <p class="text-sm text-muted-foreground">Current GPA</p>
          </div>
          <Progress :model-value="averageScore != null ? Math.min(100, Number(averageScore)) : 0" class="h-2" />
        </CardContent>
      </Card>

      <Card>
        <CardContent class="space-y-3 p-5">
          <div class="flex items-start justify-between gap-3">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.warning)" aria-hidden="true">
              <Star class="size-5" />
            </div>
            <Badge variant="secondary" class="font-normal">{{ totalGrades ?? 0 }} grades</Badge>
          </div>
          <div>
            <p class="text-3xl font-semibold tracking-tight tabular-nums">
              {{ averageScore != null ? `${Number(averageScore).toFixed(0)}%` : '—' }}
            </p>
            <p class="text-sm text-muted-foreground">Average score</p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="space-y-3 p-5">
          <div class="flex items-start justify-between gap-3">
            <div :class="cn('flex size-10 items-center justify-center rounded-xl', STATUS_TONE_ICON.success)" aria-hidden="true">
              <TrendingUp class="size-5" />
            </div>
            <Badge v-if="averageScore != null" :class="STATUS_TONE_BADGE.success" class="border font-normal">
              On record
            </Badge>
          </div>
          <div>
            <p class="text-3xl font-semibold tracking-tight tabular-nums">{{ subjectRows.length || '—' }}</p>
            <p class="text-sm text-muted-foreground">Subjects assessed</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Subject-wise marks</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4 p-5">
        <template v-if="subjectRows.length">
          <div
            v-for="row in subjectRows"
            :key="row.subject"
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4"
          >
            <div class="flex min-w-0 flex-1 items-center gap-3">
              <div :class="cn('flex size-9 shrink-0 items-center justify-center rounded-xl', STATUS_TONE_ICON.info)" aria-hidden="true">
                <component :is="subjectIcon(row.subject)" class="size-4" />
              </div>
              <div class="min-w-0 flex-1 space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="truncate text-sm font-semibold">{{ row.subject }}</p>
                  <Badge variant="outline" class="h-5 font-semibold">{{ row.grade }}</Badge>
                </div>
                <Progress :model-value="row.percent" class="h-2.5" />
              </div>
            </div>
            <p class="text-sm font-semibold tabular-nums sm:w-12 sm:text-right">{{ row.percent }}%</p>
          </div>
        </template>
        <EmptyState
          v-else
          variant="embedded"
          title="No subject marks yet"
          description="Grades recorded for this student will appear here."
        />
      </CardContent>
    </Card>

    <div class="grid gap-4 md:grid-cols-2">
      <Card>
        <CardHeader class="px-5 py-4">
          <CardTitle class="flex items-center gap-2 text-sm">
            <Star class="size-4 text-chart-3" aria-hidden="true" />
            Top subjects
          </CardTitle>
        </CardHeader>
        <CardContent class="flex flex-wrap gap-2 px-5 pb-5">
          <template v-if="topSubjects.length">
            <Badge
              v-for="row in topSubjects"
              :key="`top-${row.subject}`"
              :class="cn('gap-1 border font-medium', STATUS_TONE_BADGE.success)"
            >
              <Star class="size-3" aria-hidden="true" />
              {{ row.subject }}
            </Badge>
          </template>
          <p v-else class="text-sm text-muted-foreground">Not enough high scores yet.</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="px-5 py-4">
          <CardTitle class="flex items-center gap-2 text-sm">
            <TrendingDown class="size-4 text-destructive" aria-hidden="true" />
            Needs focus
          </CardTitle>
        </CardHeader>
        <CardContent class="flex flex-wrap gap-2 px-5 pb-5">
          <template v-if="focusSubjects.length">
            <Badge
              v-for="row in focusSubjects"
              :key="`focus-${row.subject}`"
              :class="cn('gap-1 border font-medium', STATUS_TONE_BADGE.warning)"
            >
              {{ row.subject }}
            </Badge>
          </template>
          <p v-else class="text-sm text-muted-foreground">No focus areas flagged.</p>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Recent grade results</CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <Table v-if="recentGrades.length">
          <TableHeader>
            <TableRow>
              <TableHead>Subject</TableHead>
              <TableHead>Term</TableHead>
              <TableHead>Score</TableHead>
              <TableHead>Grade</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(grade, i) in recentGrades" :key="i">
              <TableCell class="font-medium">{{ grade.subject ?? '—' }}</TableCell>
              <TableCell>{{ grade.term ?? '—' }}</TableCell>
              <TableCell>
                <div class="space-y-1">
                  <span class="tabular-nums">{{ grade.score ?? '—' }}</span>
                  <Progress
                    v-if="grade.score != null"
                    :model-value="Math.min(100, Number(grade.score))"
                    class="h-1.5 w-24"
                  />
                </div>
              </TableCell>
              <TableCell>
                <Badge
                  v-if="grade.grade"
                  :class="cn('border font-semibold', STATUS_TONE_BADGE[gradeLetterTone(String(grade.grade))])"
                >
                  {{ grade.grade }}
                </Badge>
                <span v-else>—</span>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <div v-else class="p-5">
          <EmptyState variant="embedded" title="No grade history" description="Recorded grades will list here." />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
