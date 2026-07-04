<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { BellRing, CheckCheck, Send, Users } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import MetricBand from '@/components/dashboard/MetricBand.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { platformApi, usersApi } from '@/services/api.service'

interface TrackingRow {
  id?: number
  status?: string
  channel?: string
  recipient_address?: string | null
  read_at?: string | null
  delivered_at?: string | null
  recipient?: { name?: string; email?: string | null }
  message?: { subject?: string | null; body?: string | null; sent_at?: string | null }
}

interface StaffRecipient {
  id: number
  name?: string
  email?: string | null
  role?: string | null
}

const toast = useToast()
const loading = ref(true)
const sending = ref(false)
const error = ref<string | null>(null)
const tracking = ref<TrackingRow[]>([])
const recipients = ref<StaffRecipient[]>([])
const trackingMessageId = ref<string>('')

const form = ref({
  subject: '',
  body: '',
  audience_type: 'all_staff',
  channels: ['email'] as string[],
  recipient_ids: [] as number[],
})

const channelOptions = [
  { value: 'email', label: 'Email' },
  { value: 'sms', label: 'SMS' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'push', label: 'Push' },
]

const audienceOptions = [
  { value: 'individual', label: 'Individual recipients' },
  { value: 'all_staff', label: 'All staff' },
  { value: 'admin', label: 'Admins' },
  { value: 'teacher', label: 'Teachers' },
  { value: 'finance', label: 'Finance' },
  { value: 'accounts', label: 'Accounts' },
  { value: 'examination_officer', label: 'Examination officers' },
]

const selectedRecipients = computed(() => recipients.value.filter((recipient) => form.value.recipient_ids.includes(recipient.id)))

const trackingRows = computed(() => tracking.value)

async function load() {
  loading.value = true
  error.value = null
  try {
    const [trackingPayload, peoplePayload] = await Promise.all([
      platformApi.communications.tracking(trackingMessageId.value ? { message_id: Number(trackingMessageId.value) } : undefined).catch(() => []),
      usersApi.list({ limit: 200 }).catch(() => []),
    ])
    tracking.value = Array.isArray(trackingPayload) ? trackingPayload as TrackingRow[] : []
    recipients.value = Array.isArray(peoplePayload)
      ? peoplePayload
        .map((user) => user as Record<string, unknown>)
        .map((user) => ({
          id: Number(user.id ?? 0),
          name: String(user.name ?? ((`${user.first_name ?? ''} ${user.last_name ?? ''}`.trim()) || 'User')),
          email: user.email ? String(user.email) : null,
          role: user.role ? String(user.role) : null,
        }))
        .filter((user) => user.id > 0)
      : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load platform communications')
  } finally {
    loading.value = false
  }
}

async function sendMessage() {
  sending.value = true
  try {
    await platformApi.communications.send({
      subject: form.value.subject || null,
      body: form.value.body,
      audience_type: form.value.audience_type,
      channels: form.value.channels,
      recipient_ids: form.value.audience_type === 'individual' ? form.value.recipient_ids : undefined,
    })
    toast.success('Message queued', 'Delivery tracking will update as channels process the message.')
    form.value.body = ''
    form.value.subject = ''
    form.value.recipient_ids = []
    await load()
  } catch (err) {
    toast.error('Send failed', getErrorMessage(err))
  } finally {
    sending.value = false
  }
}

function toggleChannel(channel: string, checked: boolean) {
  if (checked && !form.value.channels.includes(channel)) form.value.channels.push(channel)
  if (!checked) form.value.channels = form.value.channels.filter((item) => item !== channel)
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Platform communications"
    description="Send platform-wide announcements and inspect delivery tracking from the live API."
  >
    <MetricBand
      title="Platform communications"
      description="Send cross-school messages and inspect delivery tracking from the live platform APIs."
      :cards="[
        { title: 'Selected recipients', value: selectedRecipients.length, subtitle: 'Recipients in the current form', icon: Users },
        { title: 'Channels', value: form.channels.length, subtitle: 'Delivery channels chosen', icon: Send },
        { title: 'Tracking records', value: trackingRows.length, subtitle: 'Recent delivery rows', icon: BellRing },
        { title: 'Read receipts', value: trackingRows.filter((row) => row.read_at).length, subtitle: 'Marked as read', icon: CheckCheck },
      ]"
    />

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Send message</CardTitle>
            <CardDescription>Use the platform send endpoint to notify staff across the school network.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2 sm:col-span-2">
                <label class="text-sm font-medium">Subject</label>
                <Input v-model="form.subject" placeholder="Policy update" />
              </div>
              <div class="space-y-2 sm:col-span-2">
                <label class="text-sm font-medium">Body</label>
                <Textarea v-model="form.body" rows="6" placeholder="Write the message to send across channels..." />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Audience</label>
                <select v-model="form.audience_type" class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                  <option v-for="option in audienceOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">Message channels</label>
                <div class="flex flex-wrap gap-3 rounded-md border p-3">
                  <label v-for="channel in channelOptions" :key="channel.value" class="flex items-center gap-2 text-sm">
                    <Checkbox :checked="form.channels.includes(channel.value)" @update:checked="(checked: boolean | 'indeterminate') => toggleChannel(channel.value, checked === true)" />
                    <span>{{ channel.label }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="form.audience_type === 'individual'" class="space-y-3 rounded-lg border p-4">
              <div class="flex items-center justify-between gap-2">
                <div>
                  <p class="text-sm font-medium">Select recipients</p>
                  <p class="text-xs text-muted-foreground">Choose specific school users for an individual broadcast.</p>
                </div>
                <Badge variant="outline">{{ form.recipient_ids.length }} selected</Badge>
              </div>
              <div class="max-h-56 space-y-2 overflow-y-auto">
                <label v-for="person in recipients" :key="person.id" class="flex items-start gap-3 rounded-md border p-3 text-sm">
                  <Checkbox :checked="form.recipient_ids.includes(person.id)" @update:checked="(checked: boolean | 'indeterminate') => {
                    if (checked === true && !form.recipient_ids.includes(person.id)) form.recipient_ids.push(person.id)
                    if (checked !== true) form.recipient_ids = form.recipient_ids.filter((id) => id !== person.id)
                  }" />
                  <span class="flex-1">
                    <span class="block font-medium">{{ person.name ?? 'Unknown user' }}</span>
                    <span class="block text-xs text-muted-foreground">{{ person.email ?? 'No email available' }}</span>
                  </span>
                </label>
              </div>
            </div>

            <div class="flex justify-end">
              <Button :disabled="sending || !form.body.trim() || form.channels.length === 0" @click="sendMessage">
                <Send class="mr-2 h-4 w-4" aria-hidden="true" />
                {{ sending ? 'Sending...' : 'Send platform message' }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Delivery tracking</CardTitle>
            <CardDescription>Latest delivery rows from the platform communications API.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <Input v-model="trackingMessageId" type="number" placeholder="Filter by message ID" />
            <div class="space-y-3">
              <article v-for="row in trackingRows" :key="row.id ?? `${row.channel}-${row.recipient_address}`" class="rounded-lg border p-3">
                <div class="flex items-center justify-between gap-2">
                  <div>
                    <p class="text-sm font-medium">{{ row.message?.subject ?? 'Platform message' }}</p>
                    <p class="text-xs text-muted-foreground">{{ row.recipient?.name ?? row.recipient_address ?? 'Unknown recipient' }}</p>
                  </div>
                  <Badge variant="secondary" class="capitalize">{{ row.status ?? 'unknown' }}</Badge>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                  <span>Channel: {{ row.channel ?? '—' }}</span>
                  <span>Delivered: {{ row.delivered_at ?? '—' }}</span>
                  <span>Read: {{ row.read_at ?? '—' }}</span>
                </div>
              </article>
              <p v-if="!trackingRows.length" class="text-sm text-muted-foreground">No tracking data yet.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </template>
  </PageShell>
</template>
