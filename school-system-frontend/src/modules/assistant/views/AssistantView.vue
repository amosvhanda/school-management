<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { Bot, Loader2, Send, Sparkles } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { assistantApi } from '@/services/api.service'

interface ConversationRow {
  id: string
  title?: string
  updated_at?: string
}

interface ChatMessage {
  id: string
  role: 'user' | 'assistant' | string
  content?: string
  created_at?: string
  failed?: boolean
}

interface AssistantStatus {
  configured: boolean
  provider: string
  mode: string
  message: string
}

const toast = useToast()
const conversations = ref<ConversationRow[]>([])
const messages = ref<ChatMessage[]>([])
const activeConversationId = ref<string | null>(null)
const status = ref<AssistantStatus | null>(null)
const loading = ref(true)
const chatLoading = ref(false)
const error = ref<string | null>(null)
const prompt = ref('')
const sending = ref(false)
const threadEl = ref<HTMLElement | null>(null)

const suggestions = [
  'Show school stats',
  'How many students are enrolled?',
  'Find student Alice',
  'Search teacher Moyo',
]

const activeTitle = computed(() =>
  conversations.value.find((c) => c.id === activeConversationId.value)?.title ?? 'New conversation',
)

const modeLabel = computed(() => {
  if (!status.value) return 'Checking…'
  if (status.value.mode === 'ai') return 'AI connected'
  return 'Local school data'
})

async function scrollToBottom() {
  await nextTick()
  if (threadEl.value) {
    threadEl.value.scrollTop = threadEl.value.scrollHeight
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [statusPayload, conversationPayload] = await Promise.all([
      assistantApi.status().catch(() => null),
      assistantApi.conversations(),
    ])
    status.value = statusPayload
    conversations.value = Array.isArray(conversationPayload)
      ? conversationPayload as ConversationRow[]
      : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load assistant')
  } finally {
    loading.value = false
  }
}

async function loadConversation(id: string) {
  activeConversationId.value = id
  chatLoading.value = true
  try {
    const data = await assistantApi.getConversation(id) as { messages?: ChatMessage[] }
    messages.value = data.messages ?? []
    await scrollToBottom()
  } catch (err) {
    toast.error('Could not load conversation', getErrorMessage(err))
    messages.value = []
  } finally {
    chatLoading.value = false
  }
}

function startNewChat() {
  activeConversationId.value = null
  messages.value = []
  prompt.value = ''
}

async function sendMessage(textOverride?: string) {
  const text = (textOverride ?? prompt.value).trim()
  if (!text || sending.value) return

  sending.value = true
  const localId = `local-${Date.now()}`
  messages.value.push({ id: localId, role: 'user', content: text })
  if (!textOverride) prompt.value = ''
  await scrollToBottom()

  try {
    const data = await assistantApi.chat({
      message: text,
      conversation_id: activeConversationId.value ?? undefined,
    }) as { reply?: string; conversation_id?: string; mode?: string }

    if (data.conversation_id) {
      activeConversationId.value = data.conversation_id
    }

    messages.value.push({
      id: `reply-${Date.now()}`,
      role: 'assistant',
      content: data.reply ?? 'No response',
    })
    await load()
    await scrollToBottom()
  } catch (err) {
    const failed = messages.value.find((msg) => msg.id === localId)
    if (failed) failed.failed = true
    messages.value.push({
      id: `error-${Date.now()}`,
      role: 'assistant',
      content: getErrorMessage(err, 'Assistant unavailable'),
      failed: true,
    })
    toast.error('Assistant unavailable', getErrorMessage(err))
    await scrollToBottom()
  } finally {
    sending.value = false
  }
}

function onPromptKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void sendMessage()
  }
}

watch(messages, () => {
  void scrollToBottom()
})

onMounted(load)
</script>

<template>
  <PageShell
    title="School assistant"
    description="Ask about enrollment, fees, attendance, students, and staff — answers use your school’s live data."
    max-width="wide"
  >
    <template #actions>
      <Badge variant="outline" class="font-normal">
        <Sparkles class="mr-1 size-3.5" aria-hidden="true" />
        {{ modeLabel }}
      </Badge>
      <Button variant="outline" :disabled="loading || sending" @click="startNewChat">
        New chat
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading assistant" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div
        v-if="status"
        class="mb-4 rounded-lg border px-4 py-3 text-sm"
        :class="status.configured
          ? 'border-emerald-500/30 bg-emerald-500/5 text-foreground'
          : 'border-amber-500/30 bg-amber-500/5 text-foreground'"
        role="status"
      >
        <p class="font-medium">{{ status.configured ? 'AI mode' : 'Local school-data mode' }}</p>
        <p class="mt-1 text-muted-foreground">{{ status.message }}</p>
        <p v-if="!status.configured" class="mt-1 text-xs text-muted-foreground">
          Add <code class="rounded bg-muted px-1">OPENAI_API_KEY</code> to the Laravel
          <code class="rounded bg-muted px-1">.env</code>, then restart
          <code class="rounded bg-muted px-1">php artisan serve</code> for full AI replies.
        </p>
      </div>

      <div class="grid gap-4 lg:grid-cols-[minmax(220px,280px)_1fr]">
        <Card class="h-[min(70vh,720px)] overflow-hidden">
          <CardHeader class="pb-2">
            <CardTitle class="text-base">History</CardTitle>
            <CardDescription>Your recent assistant conversations</CardDescription>
          </CardHeader>
          <CardContent class="space-y-1 overflow-y-auto p-2">
            <button
              v-for="conv in conversations"
              :key="conv.id"
              type="button"
              class="flex w-full flex-col rounded-lg border p-3 text-left text-sm transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              :class="activeConversationId === conv.id ? 'border-primary bg-muted/40' : 'border-transparent'"
              @click="loadConversation(conv.id)"
            >
              <span class="line-clamp-2 font-medium">{{ conv.title || 'Conversation' }}</span>
              <span class="text-xs text-muted-foreground">{{ formatDateTime(conv.updated_at) }}</span>
            </button>
            <p v-if="!conversations.length" class="p-4 text-sm text-muted-foreground">
              No past conversations yet. Ask a question to start.
            </p>
          </CardContent>
        </Card>

        <Card class="flex h-[min(70vh,720px)] flex-col overflow-hidden">
          <CardHeader class="border-b pb-3">
            <CardTitle class="flex items-center gap-2 text-base">
              <Bot class="size-4" aria-hidden="true" />
              {{ activeTitle }}
            </CardTitle>
          </CardHeader>
          <CardContent class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
            <PageLoader v-if="chatLoading" label="Loading conversation" />
            <div
              v-else
              ref="threadEl"
              class="flex-1 space-y-3 overflow-y-auto"
              aria-live="polite"
            >
              <article
                v-for="msg in messages"
                :key="msg.id"
                class="rounded-lg border p-3"
                :class="[
                  msg.role === 'user' ? 'ml-8 bg-primary/5' : 'mr-8 bg-muted/30',
                  msg.failed ? 'border-destructive/40' : '',
                ]"
              >
                <p class="mb-1 text-xs font-medium capitalize text-muted-foreground">
                  {{ msg.role === 'assistant' ? 'Assistant' : 'You' }}
                </p>
                <p class="whitespace-pre-wrap text-sm">{{ msg.content }}</p>
              </article>

              <div v-if="!messages.length" class="space-y-4 py-6">
                <p class="text-sm text-muted-foreground">
                  Ask about attendance, fees, enrollment, or look up a student/teacher.
                </p>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="sending"
                    @click="sendMessage(suggestion)"
                  >
                    {{ suggestion }}
                  </Button>
                </div>
              </div>
            </div>

            <form class="flex gap-2 border-t pt-4" @submit.prevent="sendMessage()">
              <label for="assistant-prompt" class="sr-only">Message</label>
              <Textarea
                id="assistant-prompt"
                v-model="prompt"
                placeholder="Ask the school assistant…"
                rows="2"
                class="min-h-[60px] flex-1"
                :disabled="sending"
                @keydown="onPromptKeydown"
              />
              <Button type="submit" :disabled="sending || !prompt.trim()" class="self-end" aria-label="Send message">
                <Loader2 v-if="sending" class="size-4 animate-spin" aria-hidden="true" />
                <Send v-else class="size-4" aria-hidden="true" />
              </Button>
            </form>
            <p class="text-xs text-muted-foreground">
              Press Enter to send · Shift+Enter for a new line
            </p>
          </CardContent>
        </Card>
      </div>
    </template>
  </PageShell>
</template>
