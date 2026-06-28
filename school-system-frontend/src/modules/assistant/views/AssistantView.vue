<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Bot, Send } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
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
}

const toast = useToast()
const conversations = ref<ConversationRow[]>([])
const messages = ref<ChatMessage[]>([])
const activeConversationId = ref<string | null>(null)
const loading = ref(true)
const chatLoading = ref(false)
const error = ref<string | null>(null)
const prompt = ref('')
const sending = ref(false)

const activeTitle = computed(() =>
  conversations.value.find((c) => c.id === activeConversationId.value)?.title ?? 'School assistant',
)

async function loadConversations() {
  loading.value = true
  error.value = null
  try {
    conversations.value = await assistantApi.conversations() as ConversationRow[]
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
}

async function sendMessage() {
  const text = prompt.value.trim()
  if (!text) return
  sending.value = true
  messages.value.push({ id: `local-${Date.now()}`, role: 'user', content: text })
  prompt.value = ''
  try {
    const data = await assistantApi.chat({
      message: text,
      conversation_id: activeConversationId.value ?? undefined,
    }) as { reply?: string; conversation_id?: string }
    if (data.conversation_id) {
      activeConversationId.value = data.conversation_id
    }
    messages.value.push({
      id: `reply-${Date.now()}`,
      role: 'assistant',
      content: data.reply ?? 'No response',
    })
    await loadConversations()
  } catch (err) {
    toast.error('Assistant unavailable', getErrorMessage(err))
  } finally {
    sending.value = false
  }
}

onMounted(loadConversations)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">School assistant</h1>
      <p class="text-muted-foreground">Ask questions about students, finance, and school operations</p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="loadConversations" />

    <div v-else class="grid gap-4 lg:grid-cols-[minmax(220px,280px)_1fr]">
      <Card class="h-[min(70vh,640px)] overflow-hidden">
        <CardHeader class="flex flex-row items-center justify-between pb-2">
          <CardTitle class="text-base">History</CardTitle>
          <Button variant="outline" size="sm" @click="startNewChat">New</Button>
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
            <span class="font-medium line-clamp-2">{{ conv.title ?? 'Conversation' }}</span>
            <span class="text-xs text-muted-foreground">{{ conv.updated_at ?? '' }}</span>
          </button>
          <p v-if="!conversations.length" class="p-4 text-sm text-muted-foreground">No past conversations.</p>
        </CardContent>
      </Card>

      <Card class="flex h-[min(70vh,640px)] flex-col overflow-hidden">
        <CardHeader class="border-b pb-3">
          <CardTitle class="flex items-center gap-2 text-base">
            <Bot class="h-4 w-4" aria-hidden="true" />
            {{ activeTitle }}
          </CardTitle>
        </CardHeader>
        <CardContent class="flex flex-1 flex-col gap-4 overflow-hidden p-4">
          <PageLoader v-if="chatLoading" />
          <div v-else class="flex-1 space-y-3 overflow-y-auto" aria-live="polite">
            <article
              v-for="msg in messages"
              :key="msg.id"
              class="rounded-lg border p-3"
              :class="msg.role === 'user' ? 'ml-8 bg-primary/5' : 'mr-8 bg-muted/30'"
            >
              <p class="mb-1 text-xs font-medium capitalize text-muted-foreground">{{ msg.role }}</p>
              <p class="whitespace-pre-wrap text-sm">{{ msg.content }}</p>
            </article>
            <p v-if="!messages.length" class="text-sm text-muted-foreground">
              Ask about attendance, fees, enrollment, or school policies.
            </p>
          </div>
          <form class="flex gap-2 border-t pt-4" @submit.prevent="sendMessage">
            <label for="assistant-prompt" class="sr-only">Message</label>
            <Textarea
              id="assistant-prompt"
              v-model="prompt"
              placeholder="Ask the school assistant…"
              rows="2"
              class="min-h-[60px] flex-1"
            />
            <Button type="submit" :disabled="sending || !prompt.trim()" class="self-end">
              <Send class="h-4 w-4" aria-hidden="true" />
              <span class="sr-only">Send</span>
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
