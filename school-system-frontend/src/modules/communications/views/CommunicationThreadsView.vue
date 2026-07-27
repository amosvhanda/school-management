<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { MessageSquare, Plus, Send } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
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
  student?: { full_name?: string }
  parent?: { name?: string }
}

interface MessageRow {
  id: number
  body?: string
  created_at?: string
  sender?: { name?: string; first_name?: string; last_name?: string; role?: string }
}

interface ParentOption {
  id: number
  name?: string
  first_name?: string
  last_name?: string
  email?: string
}

const toast = useToast()
const threads = ref<ThreadRow[]>([])
const messages = ref<MessageRow[]>([])
const activeThread = ref<ThreadRow | null>(null)
const loading = ref(true)
const messagesLoading = ref(false)
const error = ref<string | null>(null)
const replyBody = ref('')
const sending = ref(false)

const composeOpen = ref(false)
const parents = ref<ParentOption[]>([])
const parentsLoading = ref(false)
const composeParentId = ref('')
const composeSubject = ref('')
const composeMessage = ref('')
const composing = ref(false)

const activeTitle = computed(() => activeThread.value?.subject ?? 'Select a thread')

function parentLabel(parent: ParentOption): string {
  const name = parent.name ?? [parent.first_name, parent.last_name].filter(Boolean).join(' ')
  return parent.email ? `${name || 'Parent'} · ${parent.email}` : name || `Parent #${parent.id}`
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
    threads.value = await commsApi.threads.list() as ThreadRow[]
    if (threads.value.length && !activeThread.value) {
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
  messagesLoading.value = true
  try {
    const data = await commsApi.threads.get(thread.id) as {
      thread?: ThreadRow
      messages?: MessageRow[]
    }
    messages.value = data.messages ?? []
    if (data.thread) activeThread.value = data.thread
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

async function openCompose() {
  composeParentId.value = ''
  composeSubject.value = ''
  composeMessage.value = ''
  composeOpen.value = true
  await loadParents()
}

async function submitCompose() {
  if (!composeParentId.value || !composeSubject.value.trim() || !composeMessage.value.trim()) {
    return
  }
  composing.value = true
  try {
    await commsApi.threads.create({
      parent_user_id: Number(composeParentId.value),
      subject: composeSubject.value.trim(),
      message: composeMessage.value.trim(),
    })
    composeOpen.value = false
    toast.success('Message sent')
    await loadThreads()
  } catch (err) {
    toast.error('Could not send message', getErrorMessage(err))
  } finally {
    composing.value = false
  }
}

onMounted(loadThreads)
</script>

<template>
  <PageShell
    title="Message threads"
    description="Respond to parent communications"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" @click="openCompose">
        <Plus class="h-4 w-4" aria-hidden="true" />
        New message
      </Button>
    </template>

    <PageLoader v-if="loading" />
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
            <span class="font-medium line-clamp-1">{{ thread.subject ?? 'No subject' }}</span>
            <span class="text-xs text-muted-foreground">
              {{ thread.student?.full_name ?? thread.parent?.name ?? 'Parent message' }}
            </span>
            <span v-if="thread.last_message_at" class="text-xs text-muted-foreground">
              {{ formatDateTime(thread.last_message_at) }}
            </span>
            <Badge variant="outline" class="w-fit capitalize">{{ thread.status ?? 'open' }}</Badge>
          </button>
          <p v-if="!threads.length" class="p-4 text-sm text-muted-foreground">No message threads yet.</p>
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
              <p v-if="!messages.length" class="text-sm text-muted-foreground">No messages in this thread.</p>
            </div>
            <form class="flex gap-2 border-t pt-4" @submit.prevent="sendReply">
              <label for="reply-body" class="sr-only">Reply message</label>
              <Textarea
                id="reply-body"
                v-model="replyBody"
                placeholder="Type your reply…"
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
          <DialogDescription>Start a conversation with a parent.</DialogDescription>
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
              <Send class="h-4 w-4" aria-hidden="true" />
              Send message
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
