<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { MessageSquare, Plus, Send } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { useParentPortalScope } from '@/composables/useParentPortalScope'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { parentPortalApi } from '@/services/index'

interface ThreadRow {
  id: number
  subject?: string
  status?: string
  student?: { full_name?: string }
}

interface ChildOption {
  id: number
  fullName?: string
  full_name?: string
}

interface MessageRow {
  id: number
  body?: string
  created_at?: string
  sender?: { name?: string; first_name?: string; last_name?: string }
}

const toast = useToast()
const threads = ref<ThreadRow[]>([])
const children = ref<ChildOption[]>([])
const messages = ref<MessageRow[]>([])
const activeThread = ref<ThreadRow | null>(null)
const loading = ref(true)
const messagesLoading = ref(false)
const error = ref<string | null>(null)
const replyBody = ref('')
const sending = ref(false)
const sheetOpen = ref(false)
const creating = ref(false)

const newThread = ref({
  subject: '',
  message: '',
  student_id: '',
})
const scopeStore = useParentPortalScope('messages-child')

const activeTitle = computed(() => activeThread.value?.subject ?? 'Select a conversation')

function senderName(msg: MessageRow): string {
  const s = msg.sender
  if (!s) return 'School'
  return s.name ?? ([s.first_name, s.last_name].filter(Boolean).join(' ') || 'User')
}

async function loadThreads() {
  loading.value = true
  error.value = null
  try {
    const [threadRows, childRows] = await Promise.all([
      parentPortalApi.threads() as Promise<ThreadRow[]>,
      parentPortalApi.children() as Promise<ChildOption[]>,
    ])
    threads.value = threadRows
    children.value = childRows

    const restoredChildId = scopeStore.resolveChildSelection(childRows, scopeStore.read(''), '')
    if (restoredChildId) scopeStore.write(restoredChildId)

    if (threadRows.length && !activeThread.value) {
      await selectThread(threadRows[0])
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load messages')
  } finally {
    loading.value = false
  }
}

async function selectThread(thread: ThreadRow) {
  activeThread.value = thread
  messagesLoading.value = true
  try {
    const data = await parentPortalApi.threadMessages(thread.id)
    messages.value = (data.messages ?? []) as unknown as MessageRow[]
    if (data.thread) activeThread.value = data.thread as unknown as ThreadRow
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
    await parentPortalApi.sendMessage(activeThread.value.id, { body: replyBody.value.trim() })
    replyBody.value = ''
    await selectThread(activeThread.value)
    await loadThreads()
    toast.success('Message sent')
  } catch (err) {
    toast.error('Could not send message', getErrorMessage(err))
  } finally {
    sending.value = false
  }
}

function openNewThread() {
  const restoredChildId = scopeStore.resolveChildSelection(children.value, scopeStore.read(''), '')
  newThread.value = {
    subject: '',
    message: '',
    student_id: restoredChildId,
  }
  sheetOpen.value = true
}

async function createThread() {
  if (!newThread.value.subject.trim() || !newThread.value.message.trim()) {
    toast.error('Missing fields', 'Subject and message are required.')
    return
  }
  creating.value = true
  try {
    scopeStore.write(newThread.value.student_id)
    const thread = await parentPortalApi.createThread({
      subject: newThread.value.subject.trim(),
      message: newThread.value.message.trim(),
      student_id: newThread.value.student_id ? Number(newThread.value.student_id) : undefined,
    }) as ThreadRow
    sheetOpen.value = false
    await loadThreads()
    await selectThread(thread)
    toast.success('Message sent to school')
  } catch (err) {
    toast.error('Could not start conversation', getErrorMessage(err))
  } finally {
    creating.value = false
  }
}

onMounted(loadThreads)

watch(
  () => newThread.value.student_id,
  (value) => {
    if (!sheetOpen.value) return
    scopeStore.write(value)
  },
)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Messages</h1>
        <p class="text-muted-foreground">Contact the school about your children</p>
      </div>
      <Button @click="openNewThread">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New message
      </Button>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="loadThreads" />

    <div v-else class="grid gap-4 lg:grid-cols-[minmax(240px,320px)_1fr]">
      <Card class="h-[min(70vh,640px)] overflow-hidden">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">Conversations</CardTitle>
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
            <span class="font-medium line-clamp-1">{{ thread.subject ?? 'No subject' }}</span>
            <span v-if="thread.student?.full_name" class="text-xs text-muted-foreground">
              Re: {{ thread.student.full_name }}
            </span>
            <Badge variant="outline" class="w-fit capitalize">{{ thread.status ?? 'open' }}</Badge>
          </button>
          <p v-if="!threads.length" class="p-4 text-sm text-muted-foreground">No conversations yet. Start one with New message.</p>
        </CardContent>
      </Card>

      <Card class="flex h-[min(70vh,640px)] flex-col overflow-hidden">
        <CardHeader class="border-b pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <MessageSquare class="h-4 w-4" aria-hidden="true" />
            {{ activeTitle }}
          </CardTitle>
        </CardHeader>
        <CardContent class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
          <PageLoader v-if="messagesLoading" />
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
            </div>
            <form class="flex gap-2 border-t pt-4" @submit.prevent="sendReply">
              <label for="parent-reply" class="sr-only">Reply message</label>
              <Textarea
                id="parent-reply"
                v-model="replyBody"
                placeholder="Type your message…"
                rows="3"
                class="min-h-[80px] flex-1"
              />
              <Button type="submit" :disabled="sending || !replyBody.trim()" class="self-end">
                <Send class="h-4 w-4" aria-hidden="true" />
                <span class="sr-only">Send</span>
              </Button>
            </form>
          </template>
          <p v-else class="text-sm text-muted-foreground">Select a conversation or start a new message.</p>
        </CardContent>
      </Card>
    </div>

    <Sheet v-model:open="sheetOpen">
      <SheetContent class="w-full sm:max-w-md">
        <SheetHeader>
          <SheetTitle>New message</SheetTitle>
          <SheetDescription>Send a message to school staff.</SheetDescription>
        </SheetHeader>
        <form class="mt-6 space-y-4" @submit.prevent="createThread">
          <div v-if="children.length" class="space-y-2">
            <Label for="thread-child">About child (optional)</Label>
            <Select v-model="newThread.student_id">
              <SelectTrigger id="thread-child">
                <SelectValue placeholder="Select child" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="c in children" :key="c.id" :value="String(c.id)">
                  {{ c.fullName ?? c.full_name ?? (c.student_number ? `Student ${c.student_number}` : 'Student') }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="thread-subject">Subject</Label>
            <Input id="thread-subject" v-model="newThread.subject" required />
          </div>
          <div class="space-y-2">
            <Label for="thread-message">Message</Label>
            <Textarea id="thread-message" v-model="newThread.message" rows="5" required />
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="sheetOpen = false">Cancel</Button>
            <Button type="submit" :disabled="creating">{{ creating ? 'Sending…' : 'Send' }}</Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>
  </div>
</template>
