<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
// Swapped out Sheet references for Dialog elements
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useToast } from '@/components/ui/toast/use-toast'
import { getErrorMessage } from '@/lib/api-response'
import { academicsApi } from '@/services/api.service'

interface ExamDetail {
  id: number
  name?: string
  total_marks?: number
  is_published?: boolean
  results_approved_at?: string | null
  grade_level?: { name?: string }
  subject?: { name?: string }
  students?: Array<{ id: number; full_name?: string; student_number?: string }>
  exam_results?: Array<{
    student_id: number
    marks_obtained?: number
    remarks?: string
    status?: string
    grade?: string
  }>
}

interface ResultRow {
  student_id: number
  full_name: string
  student_number: string
  marks_obtained: string
  remarks: string
  existing_grade?: string
}

const props = defineProps<{
  examId: number | null
}>()

const open = defineModel<boolean>('open', { required: true })

const { toast } = useToast()
const loading = ref(false)
const saving = ref(false)
const exam = ref<ExamDetail | null>(null)
const rows = ref<ResultRow[]>([])

const isLocked = computed(() =>
  Boolean(exam.value?.is_published || exam.value?.results_approved_at),
)

const subtitle = computed(() => {
  if (!exam.value) return ''
  const parts = [
    exam.value.subject?.name,
    exam.value.grade_level?.name,
    exam.value.total_marks ? `Out of ${exam.value.total_marks}` : null,
  ].filter(Boolean)
  return parts.join(' · ')
})

async function loadExam(id: number) {
  loading.value = true
  try {
    const data = await academicsApi.exams.get(id) as ExamDetail
    exam.value = data

    const existing = new Map(
      (data.exam_results ?? []).map((r) => [r.student_id, r]),
    )

    rows.value = (data.students ?? []).map((student) => {
      const prior = existing.get(student.id)
      return {
        student_id: student.id,
        full_name: student.full_name ?? `Student #${student.id}`,
        student_number: student.student_number ?? '—',
        marks_obtained: prior?.marks_obtained != null ? String(prior.marks_obtained) : '',
        remarks: prior?.remarks ?? '',
        existing_grade: prior?.grade,
      }
    })
  } catch (err) {
    toast({
      title: 'Could not load exam',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
    open.value = false
  } finally {
    loading.value = false
  }
}

async function saveResults() {
  if (!exam.value || isLocked.value) return

  const results = rows.value
    .filter((r) => r.marks_obtained !== '')
    .map((r) => ({
      student_id: r.student_id,
      marks_obtained: Number(r.marks_obtained),
      remarks: r.remarks.trim() || undefined,
    }))

  if (!results.length) {
    toast({
      title: 'No marks entered',
      description: 'Please enter at least one student score before saving.',
      variant: 'destructive',
    })
    return
  }

  const max = Number(exam.value.total_marks ?? 100)
  const invalid = results.find((r) => r.marks_obtained < 0 || r.marks_obtained > max)
  if (invalid) {
    toast({
      title: 'Invalid score detected',
      description: `Marks must fall within the range of 0 to ${max}.`,
      variant: 'destructive',
    })
    return
  }

  saving.value = true
  try {
    await academicsApi.exams.recordResults(exam.value.id, { results })
    toast({
      title: 'Results saved successfully',
      description: `${results.length} student marks record(s) have been modified.`,
    })
    open.value = false
  } catch (err) {
    toast({
      title: 'Save failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    saving.value = false
  }
}

watch(
  () => [open.value, props.examId] as const,
  ([isOpen, id]) => {
    if (isOpen && id) loadExam(id)
    if (!isOpen) {
      exam.value = null
      rows.value = []
    }
  },
)
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="flex max-h-[85vh] w-full max-w-2xl flex-col gap-0 overflow-hidden p-0 rounded-xl shadow-lg border">
      <DialogHeader class="shrink-0 space-y-1 border-b border-muted/60 px-6 pb-4 pt-6">
        <DialogTitle class="text-base font-semibold tracking-tight">
          {{ exam?.name ?? 'Enter exam results' }}
        </DialogTitle>
        <DialogDescription class="text-xs leading-relaxed">
          {{ subtitle || 'Record and modify grades matching student rosters.' }}
        </DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto px-6 py-4">
        <div
          v-if="isLocked"
          class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/5 p-3 text-xs text-amber-600 dark:text-amber-500 leading-normal"
        >
          Results are approved or published — marks cannot be modified.
        </div>

        <PageLoader v-if="loading" class="py-12" label="Loading roster fields…" />

        <template v-else-if="exam">
          <Table v-if="rows.length">
            <TableHeader>
              <TableRow>
                <TableHead class="text-xs font-medium">Student</TableHead>
                <TableHead class="w-28 text-xs font-medium">Score</TableHead>
                <TableHead class="text-xs font-medium">Remarks</TableHead>
                <TableHead class="w-16 text-xs font-medium">Grade</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in rows" :key="row.student_id" class="transition-colors">
                <TableCell class="py-2.5">
                  <p class="font-medium text-sm text-foreground leading-none">{{ row.full_name }}</p>
                  <p class="text-xs text-muted-foreground mt-1 font-mono tracking-tight">{{ row.student_number }}</p>
                </TableCell>
                <TableCell class="py-2.5">
                  <Label :for="`marks-${row.student_id}`" class="sr-only">Score for {{ row.full_name }}</Label>
                  <Input
                    :id="`marks-${row.student_id}`"
                    v-model="row.marks_obtained"
                    type="number"
                    min="0"
                    :max="exam.total_marks"
                    step="0.01"
                    :disabled="isLocked"
                    class="h-9 text-sm"
                    :placeholder="` / ${exam.total_marks}`"
                  />
                </TableCell>
                <TableCell class="py-2.5">
                  <Input
                    v-model="row.remarks"
                    type="text"
                    :disabled="isLocked"
                    class="h-9 text-sm"
                    placeholder="Optional notes"
                  />
                </TableCell>
                <TableCell class="py-2.5">
                  <Badge v-if="row.existing_grade" variant="outline" class="font-semibold text-xs px-2 py-0.5">
                    {{ row.existing_grade }}
                  </Badge>
                  <span v-else class="text-muted-foreground/60 text-xs pl-2">—</span>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

          <p v-else class="py-12 text-center text-sm text-muted-foreground italic" role="status">
            No active students found assigned to this module grade tier.
          </p>
        </template>
      </div>

      <DialogFooter
        v-if="!isLocked && exam && rows.length"
        class="shrink-0 border-t border-muted/60 px-6 py-4 sm:flex-row sm:justify-end gap-2"
      >
        <Button type="button" variant="outline" :disabled="saving" @click="open = false">
          Cancel
        </Button>
        <Button type="button" :disabled="saving" @click="saveResults">
          {{ saving ? 'Saving…' : 'Save changes' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
