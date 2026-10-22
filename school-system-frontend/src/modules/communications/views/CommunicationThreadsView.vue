<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { MessageSquare, Plus, Send } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { commsApi } from '@/services/api.service'

interface ThreadRow {
  id: number
  subject?: string
  status?: string
  last_message_at?: string
  unread_count?: number
  staff_user_id?: number | null
  student_id?: number | null
  student?: { full_name?: string; student_number?: string }
  parent?: { name?: string; email?: string }
  staff?: { id?: number; name?: string }
}

interface MessageRow {
  id: number
  body?: string
  created_at?: string
  read_at?: string | null
  sender?: { name?: string; first_name?: string; last_name?: string; role?: string }
}

interface ParentOption {
  id: number
  name?: string
  first_name?: string
  last_name?: string
  email?: string
}

interface StudentOption {
  id: number
  full_name?: string
  student_number?: string
}

interface StaffOption {
  id: number
  name?: string
  first_name?: string
  last_name?: string
  email?: string
  role?: string
}

const toast = useToast()
const route = useRoute()
const threads = ref<ThreadRow[]>([])
const messages = ref<MessageRow[]>([])
const activeThread = ref<ThreadRow | null>(null)
const loading = ref(true)
const messagesLoading = ref(false)
const error = ref<string | null>(null)
const replyBody = ref('')
const sending = ref(false)
const updating = ref(false)

const search = ref('')
const statusFilter = ref<'all' | 'open' | 'closed'>('open')

const composeOpen = ref(false)
const parents = ref<ParentOption[]>([])
const parentStudents = ref<StudentOption[]>([])
const staffOptions = ref<StaffOption[]>([])
const parentsLoading = ref(false)
const studentsLoading = ref(false)
const composeParentId = ref('')
const composeStudentId = ref('')
const composeSubject = ref('')
const composeMessage = ref('')
const composing = ref(false)
const assignStaffId = ref('')

const activeTitle = computed(() => activeThread.value?.subject ?? 'Select a thread')
const isClosed = computed(() => (activeThread.value?.status ?? 'open') === 'closed')

function parentLabel(parent: ParentOption): string {
  const name = parent.name ?? [parent.first_name, parent.last_name].filter(Boolean).join(' ')
  return parent.email ? `${name || 'Parent'} · ${parent.email}` : name || `Parent #${parent.id}`
}

function staffLabel(staff: StaffOption): string {
  const name = staff.name ?? [staff.first_name, staff.last_name].filter(Boolean).join(' ')
  return staff.role ? `${name || 'Staff'} (${staff.role})` : name || `Staff #${staff.id}`
}

function senderName(msg: MessageRow): string {
  const s = msg.sender
  if (!s) return 'Unknown'
  return s.name ?? ([s.first_name, s.last_name].filter(Boolean).join(' ') || 'User')
}

async function loadThreads() {
  loading.value = true
  error.value = null
  try {
    const params: Record<string, string | number | boolean> = {
      per_page: 50,
    }
    if (statusFilter.value !== 'all') params.status = statusFilter.value
    if (search.value.trim()) params.search = search.value.trim()

    threads.value = (await commsApi.threads.list(params)) as ThreadRow[]

    const threadParam = Array.isArray(route.query.thread)
      ? route.query.thread[0]
      : route.query.thread
    const deepLinkId = threadParam ? Number(threadParam) : NaN

    if (Number.isFinite(deepLinkId) && deepLinkId > 0) {
      const match = threads.value.find((t) => t.id === deepLinkId)
      if (match) {
        await selectThread(match)
        return
      }
      try {
        await selectThread({ id: deepLinkId })
        return
      } catch {
        // fall through to default selection
      }
    }

    if (activeThread.value) {
      const refreshed = threads.value.find((t) => t.id === activeThread.value?.id)
      if (refreshed) activeThread.value = refreshed
      else if (threads.value.length) await selectThread(threads.value[0])
      else {
        activeThread.value = null
        messages.value = []
      }
    } else if (threads.value.length) {
      await selectThread(threads.value[0])
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load threads')
  } finally {
    loading.value = false
  }
}

async function selectThread(thread: ThreadRow) {
  activeThread.value = thread
  assignStaffId.value = thread.staff_user_id ? String(thread.staff_user_id) : '__none__'
  messagesLoading.value = true
  try {
    const data = await commsApi.threads.get(thread.id) as {
      thread?: ThreadRow
      messages?: MessageRow[]
    }
    messages.value = data.messages ?? []
    if (data.thread) {
      activeThread.value = data.thread
      assignStaffId.value = data.thread.staff_user_id ? String(data.thread.staff_user_id) : '__none__'
    }
    const row = threads.value.find((t) => t.id === thread.id)
    if (row) row.unread_count = 0
  } catch (err) {
    toast.error('Could not load messages', getErrorMessage(err))
    messages.value = []
  } finally {
    messagesLoading.value = false
  }
}

async function sendReply() {
  if (!activeThread.value || !replyBody.value.trim()) return
  sending.value = true
  try {
    await commsApi.threads.reply(activeThread.value.id, { body: replyBody.value.trim() })
    replyBody.value = ''
    await selectThread(activeThread.value)
    await loadThreads()
    toast.success('Reply sent')
  } catch (err) {
    toast.error('Could not send reply', getErrorMessage(err))
  } finally {
    sending.value = false
  }
}

async function setStatus(status: 'open' | 'closed') {
  if (!activeThread.value) return
  updating.value = true
  try {
    const updated = await commsApi.threads.update(activeThread.value.id, { status }) as ThreadRow
    activeThread.value = { ...activeThread.value, ...updated }
    toast.success(status === 'closed' ? 'Thread closed' : 'Thread reopened')
    await loadThreads()
  } catch (err) {
    toast.error('Could not update thread', getErrorMessage(err))
  } finally {
    updating.value = false
  }
}

async function assignStaff() {
  if (!activeThread.value) return
  updating.value = true
  try {
    const payload = {
      staff_user_id: assignStaffId.value && assignStaffId.value !== '__none__'
        ? Number(assignStaffId.value)
        : null,
    }
    const updated = await commsApi.threads.update(activeThread.value.id, payload) as ThreadRow
    activeThread.value = { ...activeThread.value, ...updated }
    toast.success(assignStaffId.value ? 'Thread assigned' : 'Assignment cleared')
    await loadThreads()
  } catch (err) {
    toast.error('Could not assign thread', getErrorMessage(err))
  } finally {
    updating.value = false
  }
}

async function loadParents() {
  if (parents.value.length) return
  parentsLoading.value = true
  try {
    parents.value = (await commsApi.parents()) as ParentOption[]
  } catch (err) {
    toast.error('Could not load parents', getErrorMessage(err))
  } finally {
    parentsLoading.value = false
  }
}

async function loadStaffOptions() {
  if (staffOptions.value.length) return
  try {
    staffOptions.value = (await commsApi.staff()) as StaffOption[]
  } catch (err) {
    toast.error('Could not load staff', getErrorMessage(err))
  }
}

async function loadParentStudents(parentId: string) {
  parentStudents.value = []
  composeStudentId.value = '__none__'
  if (!parentId) return
  studentsLoading.value = true
  try {
    parentStudents.value = (await commsApi.parentStudents(Number(parentId))) as StudentOption[]
    if (parentStudents.value.length === 1) {
      composeStudentId.value = String(parentStudents.value[0].id)
    }
  } catch (err) {
    toast.error('Could not load children', getErrorMessage(err))
  } finally {
    studentsLoading.value = false
  }
}

async function openCompose() {
  composeParentId.value = ''
  composeStudentId.value = '__none__'
  composeSubject.value = ''
  composeMessage.value = ''
  parentStudents.value = []
  composeOpen.value = true
  await loadParents()
}

async function submitCompose() {
  if (!composeParentId.value || !composeSubject.value.trim() || !composeMessage.value.trim()) {
    return
  }
  composing.value = true
  try {
    const payload: Record<string, unknown> = {
      parent_user_id: Number(composeParentId.value),
      subject: composeSubject.value.trim(),
      message: composeMessage.value.trim(),
    }
    if (composeStudentId.value && composeStudentId.value !== '__none__') {
      payload.student_id = Number(composeStudentId.value)
    }
    await commsApi.threads.create(payload)
    composeOpen.value = false
    toast.success('Message sent')
    await loadThreads()
  } catch (err) {
    toast.error('Could not send message', getErrorMessage(err))
  } finally {
    composing.value = false
  }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    void loadThreads()
  }, 300)
})

watch(statusFilter, () => {
  void loadThreads()
})

watch(composeParentId, (id) => {
  void loadParentStudents(id)
})

onMounted(async () => {
  await Promise.all([loadThreads(), loadStaffOptions()])
})

watch(
  () => route.query.thread,
  () => {
    void loadThreads()
  },
)
</script>

<template>
  <section class="space-y-4" aria-label="Message threads">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
        <Label for="thread-search" class="sr-only">Search threads</Label>
        <Input
          id="thread-search"
          v-model="search"
          placeholder="Search subject, parent, or student…"
          class="max-w-sm"
        />
        <Select v-model="statusFilter">
          <SelectTrigger class="w-[140px]" aria-label="Filter by status">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="open">Open</SelectItem>
            <SelectItem value="closed">Closed</SelectItem>
            <SelectItem value="all">All</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <Button type="button" size="sm" @click="openCompose">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New message
      </Button>
    </div>

    <PageLoader v-if="loading" label="Loading messages" />
    <ErrorState v-else-if="error" :description="error" @retry="loadThreads" />

    <div v-else class="grid gap-4 lg:grid-cols-[minmax(240px,320px)_1fr]">
      <Card class="h-[min(70vh,640px)] overflow-hidden">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">Inbox</CardTitle>
        </CardHeader>
        <CardContent class="space-y-1 overflow-y-auto p-2">
          <button
            v-for="thread in threads"
            :key="thread.id"
            type="button"
            class="flex w-full flex-col gap-1 rounded-lg border p-3 text-left transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="activeThread?.id === thread.id ? 'border-primary bg-muted/40' : 'border-transparent'"
            @click="selectThread(thread)"
          >
            <span class="flex items-start justify-between gap-2">
              <span class="font-medium line-clamp-1" :class="(thread.unread_count ?? 0) > 0 ? 'font-semibold' : ''">
                {{ thread.subject ?? 'No subject' }}
              </span>
              <Badge
                v-if="(thread.unread_count ?? 0) > 0"
                variant="default"
                class="shrink-0"
                :aria-label="`${thread.unread_count} unread`"
              >
                {{ thread.unread_count }}
              </Badge>
            </span>
            <span class="text-xs text-muted-foreground">
              {{ thread.student?.full_name ?? thread.parent?.name ?? 'Parent message' }}
            </span>
            <span v-if="thread.last_message_at" class="text-xs text-muted-foreground">
              {{ formatDateTime(thread.last_message_at) }}
            </span>
            <div class="flex flex-wrap gap-1">
              <Badge variant="outline" class="w-fit capitalize">{{ thread.status ?? 'open' }}</Badge>
              <Badge v-if="!thread.staff_user_id" variant="secondary" class="w-fit">Unassigned</Badge>
            </div>
          </button>
          <EmptyState
            v-if="!threads.length"
            variant="embedded"
            title="No threads"
            description="No message threads match this filter."
          />
        </CardContent>
      </Card>

      <Card class="flex h-[min(70vh,640px)] flex-col overflow-hidden">
        <CardHeader class="border-b pb-3">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <CardTitle class="flex items-center gap-2 text-base">
              <MessageSquare class="h-4 w-4" aria-hidden="true" />
              {{ activeTitle }}
            </CardTitle>
            <div v-if="activeThread" class="flex flex-wrap gap-2">
              <Button
                v-if="!isClosed"
                type="button"
                size="sm"
                variant="outline"
                :disabled="updating"
                @click="setStatus('closed')"
              >
                Close
              </Button>
              <Button
                v-else
                type="button"
                size="sm"
                variant="outline"
                :disabled="updating"
                @click="setStatus('open')"
              >
                Reopen
              </Button>
            </div>
          </div>
          <div v-if="activeThread" class="mt-3 flex flex-wrap items-end gap-2">
            <div class="min-w-[180px] flex-1 space-y-1">
              <Label for="assign-staff">Assigned staff</Label>
              <Select v-model="assignStaffId">
                <SelectTrigger id="assign-staff">
                  <SelectValue placeholder="Unassigned" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">Unassigned</SelectItem>
                  <SelectItem
                    v-for="member in staffOptions"
                    :key="member.id"
                    :value="String(member.id)"
                  >
                    {{ staffLabel(member) }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Button type="button" size="sm" variant="secondary" :disabled="updating" @click="assignStaff">
              Save assignment
            </Button>
          </div>
        </CardHeader>
        <CardContent class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
          <PageLoader v-if="messagesLoading" label="Loading conversation" />
          <template v-else-if="activeThread">
            <div class="flex-1 space-y-3 overflow-y-auto" aria-live="polite">
              <article
                v-for="msg in messages"
                :key="msg.id"
                class="rounded-lg border bg-muted/30 p-3"
              >
                <header class="mb-1 flex items-center justify-between gap-2 text-xs text-muted-foreground">
                  <span class="font-medium text-foreground">{{ senderName(msg) }}</span>
                  <time>{{ formatDateTime(msg.created_at) }}</time>
                </header>
                <p class="whitespace-pre-wrap text-sm">{{ msg.body }}</p>
              </article>
              <p v-if="!messages.length" class="text-sm text-muted-foreground">No messages in this thread.</p>
            </div>
            <form class="flex gap-2 border-t pt-4" @submit.prevent="sendReply">
              <label for="reply-body" class="sr-only">Reply message</label>
              <Textarea
                id="reply-body"
                v-model="replyBody"
                :placeholder="isClosed ? 'Reopen by sending a reply…' : 'Type your reply…'"
                rows="3"
                class="min-h-[80px] flex-1"
              />
              <Button type="submit" :disabled="sending || !replyBody.trim()" class="self-end">
                <Send class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">Send</span>
              </Button>
            </form>
          </template>
          <p v-else class="text-sm text-muted-foreground">Select a thread to view messages.</p>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="composeOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>New message</DialogTitle>
          <DialogDescription>Start a conversation with a parent about a specific child when possible.</DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCompose">
          <div class="space-y-2">
            <Label for="compose-parent">Parent</Label>
            <Select v-model="composeParentId" :disabled="parentsLoading">
              <SelectTrigger id="compose-parent">
                <SelectValue :placeholder="parentsLoading ? 'Loading parents…' : 'Select a parent'" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="parent in parents"
                  :key="parent.id"
                  :value="String(parent.id)"
                >
                  {{ parentLabel(parent) }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="!parentsLoading && !parents.length" class="text-xs text-muted-foreground">
              No parents found for this school.
            </p>
          </div>
          <div class="space-y-2">
            <Label for="compose-student">Student (optional)</Label>
            <Select v-model="composeStudentId" :disabled="!composeParentId || studentsLoading">
              <SelectTrigger id="compose-student">
                <SelectValue
                  :placeholder="studentsLoading ? 'Loading children…' : 'Select a student'"
                />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="__none__">No specific student</SelectItem>
                <SelectItem
                  v-for="student in parentStudents"
                  :key="student.id"
                  :value="String(student.id)"
                >
                  {{ student.full_name }}{{ student.student_number ? ` · ${student.student_number}` : '' }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="compose-subject">Subject</Label>
            <Input id="compose-subject" v-model="composeSubject" placeholder="Message subject" maxlength="255" />
          </div>
          <div class="space-y-2">
            <Label for="compose-message">Message</Label>
            <Textarea
              id="compose-message"
              v-model="composeMessage"
              rows="4"
              placeholder="Write your message…"
              class="min-h-[120px]"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="composeOpen = false">Cancel</Button>
            <Button
              type="submit"
              :disabled="composing || !composeParentId || !composeSubject.trim() || !composeMessage.trim()"
            >
              <Send class="mr-2 h-4 w-4" aria-hidden="true" />
              Send message
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </section>
</template>
