<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CheckCircle2, CircleDashed } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import { parentPortalApi } from '@/services/index'

type Decision = 'approved' | 'declined' | 'skip'

interface ConsentForm {
  id: number
  title?: string
  content?: string
  description?: string
  status?: string
  response_status?: string
  responses_by_student?: Record<number | string, string>
  pending_student_ids?: number[]
  due_date?: string | null
}

interface ChildOption {
  id: number
  fullName?: string
  full_name?: string
  student_number?: string
}

const toast = useToast()
const forms = ref<ConsentForm[]>([])
const children = ref<ChildOption[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const activeForm = ref<ConsentForm | null>(null)
const notes = ref('')
const decisions = ref<Record<number, Decision>>({})
const saving = ref(false)

const decidedCount = computed(() =>
  Object.values(decisions.value).filter((d) => d === 'approved' || d === 'declined').length,
)

const approvedCount = computed(() =>
  Object.values(decisions.value).filter((d) => d === 'approved').length,
)

const declinedCount = computed(() =>
  Object.values(decisions.value).filter((d) => d === 'declined').length,
)

const childLabel = (child: ChildOption) =>
  child.fullName
  ?? child.full_name
  ?? (child.student_number ? `Student ${child.student_number}` : `Student #${child.id}`)

function statusForChild(form: ConsentForm, studentId: number) {
  return form.responses_by_student?.[studentId]
    ?? form.responses_by_student?.[String(studentId)]
    ?? null
}

function formBody(form: ConsentForm) {
  return String(form.content || form.description || '').trim()
}

function excerpt(form: ConsentForm, max = 140) {
  const text = formBody(form)
  if (text.length <= max) return text
  return `${text.slice(0, max).trimEnd()}…`
}

function formBadgeLabel(form: ConsentForm) {
  const status = form.response_status ?? 'pending'
  if (status === 'partial') return 'Partially complete'
  if (status === 'mixed') return 'Mixed'
  if (status === 'approved') return 'Approved'
  if (status === 'declined') return 'Declined'
  return 'Action needed'
}

function formBadgeVariant(form: ConsentForm): 'default' | 'secondary' | 'outline' | 'destructive' {
  const status = form.response_status ?? 'pending'
  if (status === 'approved') return 'default'
  if (status === 'declined') return 'destructive'
  if (status === 'partial' || status === 'mixed') return 'secondary'
  return 'outline'
}

function pendingCount(form: ConsentForm) {
  return (form.pending_student_ids ?? []).length
}

function summaryLine(form: ConsentForm) {
  if (!children.value.length) return null
  const pending = pendingCount(form)
  const total = children.value.length
  if (pending === 0) return `Complete for all ${total} ${total === 1 ? 'child' : 'children'}`
  if (pending === total) return `Needed for ${total} ${total === 1 ? 'child' : 'children'}`
  return `${pending} of ${total} children still need a response`
}

function childStatusLabel(status: string | null) {
  if (!status) return 'Pending'
  if (status === 'approved') return 'Approved'
  if (status === 'declined') return 'Declined'
  return status
}

function decisionFor(studentId: number): Decision {
  return decisions.value[studentId] ?? 'skip'
}

function setDecision(studentId: number, decision: Decision) {
  decisions.value = { ...decisions.value, [studentId]: decision }
}

function approveAll() {
  const next: Record<number, Decision> = {}
  for (const child of children.value) next[child.id] = 'approved'
  decisions.value = next
}

function declineAll() {
  const next: Record<number, Decision> = {}
  for (const child of children.value) next[child.id] = 'declined'
  decisions.value = next
}

function pendingApprove() {
  const pending = new Set((activeForm.value?.pending_student_ids ?? []).map(Number))
  const next: Record<number, Decision> = {}
  for (const child of children.value) {
    next[child.id] = pending.size === 0 || pending.has(child.id) ? 'approved' : 'skip'
  }
  decisions.value = next
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [formRows, childRows] = await Promise.all([
      parentPortalApi.consentForms() as Promise<ConsentForm[]>,
      parentPortalApi.children() as Promise<ChildOption[]>,
    ])
    forms.value = formRows
    children.value = childRows
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load consent forms')
  } finally {
    loading.value = false
  }
}

function openRespond(form: ConsentForm) {
  activeForm.value = form
  notes.value = ''
  const pending = new Set((form.pending_student_ids ?? []).map(Number))
  const next: Record<number, Decision> = {}
  for (const child of children.value) {
    const existing = statusForChild(form, child.id)
    if (existing === 'approved' || existing === 'declined') {
      next[child.id] = existing
    } else if (pending.size === 0 || pending.has(child.id)) {
      next[child.id] = 'approved'
    } else {
      next[child.id] = 'skip'
    }
  }
  decisions.value = next
  sheetOpen.value = true
}

function closeSheet() {
  sheetOpen.value = false
  activeForm.value = null
  notes.value = ''
  decisions.value = {}
}

async function submitResponses() {
  if (!activeForm.value) return

  const responses = Object.entries(decisions.value)
    .filter(([, status]) => status === 'approved' || status === 'declined')
    .map(([studentId, status]) => ({
      student_id: Number(studentId),
      status,
    }))

  if (children.value.length && responses.length === 0) {
    toast.error('Choose a decision', 'Approve or decline at least one child.')
    return
  }

  saving.value = true
  try {
    await parentPortalApi.respondConsent(activeForm.value.id, {
      notes: notes.value.trim() || undefined,
      responses,
    })
    toast.success(
      'Responses saved',
      approvedCount.value && declinedCount.value
        ? `${approvedCount.value} approved, ${declinedCount.value} declined.`
        : declinedCount.value
          ? `${declinedCount.value} declined.`
          : `${approvedCount.value} approved.`,
    )
    closeSheet()
    await load()
  } catch (err) {
    toast.error('Could not submit response', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Consent forms"
    description="Approve or decline separately for each child on the same form."
    max-width="wide"
  >
    <PageLoader v-if="loading" label="Loading consent forms" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <EmptyState
      v-else-if="!forms.length"
      title="You're all caught up"
      description="There are no consent forms waiting for your response."
    />

    <section v-else class="space-y-4" aria-label="Consent forms">
      <article v-for="form in forms" :key="form.id">
        <Card class="overflow-hidden">
          <CardHeader class="space-y-3 pb-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0 space-y-1">
                <CardTitle class="text-lg leading-snug">
                  {{ form.title ?? 'Consent form' }}
                </CardTitle>
                <CardDescription v-if="summaryLine(form)">
                  {{ summaryLine(form) }}
                </CardDescription>
              </div>
              <Badge :variant="formBadgeVariant(form)">
                {{ formBadgeLabel(form) }}
              </Badge>
            </div>
            <p v-if="formBody(form)" class="text-sm leading-relaxed text-muted-foreground">
              {{ excerpt(form) }}
            </p>
          </CardHeader>

          <Separator />

          <CardContent class="flex flex-col gap-4 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <ul
              v-if="children.length"
              class="flex flex-wrap gap-x-4 gap-y-2 text-sm"
              aria-label="Status by child"
            >
              <li
                v-for="child in children"
                :key="`${form.id}-${child.id}`"
                class="inline-flex items-center gap-1.5 text-muted-foreground"
              >
                <CheckCircle2
                  v-if="statusForChild(form, child.id) === 'approved'"
                  class="h-3.5 w-3.5 text-foreground"
                  aria-hidden="true"
                />
                <CircleDashed
                  v-else
                  class="h-3.5 w-3.5"
                  aria-hidden="true"
                />
                <span class="text-foreground">{{ childLabel(child) }}</span>
                <span class="sr-only">{{ childStatusLabel(statusForChild(form, child.id)) }}</span>
                <span aria-hidden="true" class="text-xs capitalize">
                  · {{ childStatusLabel(statusForChild(form, child.id)) }}
                </span>
              </li>
            </ul>
            <Button
              class="w-full shrink-0 sm:w-auto"
              :variant="pendingCount(form) ? 'default' : 'outline'"
              @click="openRespond(form)"
            >
              {{ pendingCount(form) ? 'Respond' : 'Update response' }}
            </Button>
          </CardContent>
        </Card>
      </article>
    </section>

    <Sheet
      :open="sheetOpen"
      @update:open="(open) => { if (!open) closeSheet() }"
    >
      <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
        <SheetHeader class="space-y-2 border-b px-6 py-5 text-left">
          <SheetTitle>{{ activeForm?.title ?? 'Consent form' }}</SheetTitle>
          <SheetDescription>
            Set approve or decline for each child. Skip anyone you do not want to change.
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
          <section v-if="activeForm && formBody(activeForm)" class="space-y-2">
            <h3 class="text-sm font-medium">Request details</h3>
            <div class="rounded-lg bg-muted/50 p-4 text-sm leading-relaxed text-muted-foreground whitespace-pre-wrap">
              {{ formBody(activeForm) }}
            </div>
          </section>

          <section v-if="children.length" class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2">
              <div>
                <h3 class="text-sm font-medium">Your children</h3>
                <p class="text-sm text-muted-foreground">
                  {{ decidedCount }} decision{{ decidedCount === 1 ? '' : 's' }} ready
                </p>
              </div>
              <div class="flex flex-wrap gap-1">
                <Button type="button" variant="ghost" size="sm" @click="approveAll">
                  Approve all
                </Button>
                <Button type="button" variant="ghost" size="sm" @click="declineAll">
                  Decline all
                </Button>
                <Button type="button" variant="ghost" size="sm" @click="pendingApprove">
                  Pending only
                </Button>
              </div>
            </div>

            <ul class="divide-y rounded-lg border" aria-label="Per-child decisions">
              <li
                v-for="child in children"
                :key="child.id"
                class="space-y-3 px-4 py-4"
              >
                <div class="min-w-0">
                  <p class="text-sm font-medium leading-none">{{ childLabel(child) }}</p>
                  <p class="mt-1 text-xs text-muted-foreground">
                    Current: {{ childStatusLabel(activeForm ? statusForChild(activeForm, child.id) : null) }}
                  </p>
                </div>
                <div
                  class="grid grid-cols-3 gap-2"
                  role="group"
                  :aria-label="`Decision for ${childLabel(child)}`"
                >
                  <Button
                    type="button"
                    size="sm"
                    :variant="decisionFor(child.id) === 'approved' ? 'default' : 'outline'"
                    :aria-pressed="decisionFor(child.id) === 'approved'"
                    :class="cn(decisionFor(child.id) === 'approved' && 'ring-1 ring-ring')"
                    @click="setDecision(child.id, 'approved')"
                  >
                    Approve
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    :variant="decisionFor(child.id) === 'declined' ? 'destructive' : 'outline'"
                    :aria-pressed="decisionFor(child.id) === 'declined'"
                    @click="setDecision(child.id, 'declined')"
                  >
                    Decline
                  </Button>
                  <Button
                    type="button"
                    size="sm"
                    :variant="decisionFor(child.id) === 'skip' ? 'secondary' : 'ghost'"
                    :aria-pressed="decisionFor(child.id) === 'skip'"
                    @click="setDecision(child.id, 'skip')"
                  >
                    Skip
                  </Button>
                </div>
              </li>
            </ul>
          </section>

          <section class="space-y-2">
            <Label for="consent-notes">
              Notes <span class="font-normal text-muted-foreground">(optional)</span>
            </Label>
            <Textarea
              id="consent-notes"
              v-model="notes"
              rows="3"
              placeholder="Add a short note for the school if needed"
            />
          </section>
        </div>

        <SheetFooter class="gap-2 border-t px-6 py-4 sm:flex-row sm:justify-between">
          <Button variant="outline" :disabled="saving" @click="closeSheet">
            Cancel
          </Button>
          <Button :disabled="saving || decidedCount === 0" @click="submitResponses">
            {{
              saving
                ? 'Saving…'
                : decidedCount > 1
                  ? `Save ${decidedCount} responses`
                  : 'Save response'
            }}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </PageShell>
</template>
