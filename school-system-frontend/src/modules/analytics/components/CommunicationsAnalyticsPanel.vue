<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Megaphone, MessageSquare, Users } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { formatDateTime } from '@/lib/format'
import type {
  AnnouncementRow,
  CommunicationThreadRow,
} from '@/modules/analytics/types/communications-analytics'
import { audienceLabel } from '@/modules/communications/announcement-form'

const props = defineProps<{
  threads: CommunicationThreadRow[]
  announcements: AnnouncementRow[]
  unassignedThreads: number
  openThreads: number
  activeAnnouncements: number
}>()

const recentThreads = computed(() =>
  [...props.threads]
    .sort((a, b) => String(b.last_message_at ?? '').localeCompare(String(a.last_message_at ?? '')))
    .slice(0, 8),
)

const recentAnnouncements = computed(() =>
  [...props.announcements]
    .sort((a, b) => String(b.date ?? b.created_at ?? '').localeCompare(String(a.date ?? a.created_at ?? '')))
    .slice(0, 5),
)

function threadContact(thread: CommunicationThreadRow): string {
  return thread.parent?.name
    ?? thread.student?.full_name
    ?? 'Parent'
}

function threadAssignee(thread: CommunicationThreadRow): string {
  if (thread.staff?.name) return thread.staff.name
  if (thread.staff_user_id) return 'Assigned'
  return 'Unassigned'
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h3 class="text-sm font-semibold tracking-tight">How messaging works</h3>
      <p class="mt-1 text-xs text-muted-foreground">
        Analytics counts activity from two channels — they are separate from each other.
      </p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <Card>
        <CardHeader class="border-b border-border/60 pb-4">
          <CardTitle class="flex items-center gap-2 text-base">
            <MessageSquare class="h-4 w-4 text-primary" aria-hidden="true" />
            Parent ↔ staff threads
          </CardTitle>
          <CardDescription>
            Parents start conversations from the portal; staff reply from the inbox.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
          <ol class="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
            <li>
              Parent logs in at <strong class="text-foreground">/portal/messages</strong> and clicks
              <strong class="text-foreground">New message</strong> (optionally about a child).
            </li>
            <li>
              The thread appears in staff inbox at
              <RouterLink to="/communications/threads" class="font-medium text-primary underline-offset-4 hover:underline">
                Messages
              </RouterLink>.
            </li>
            <li>
              When a teacher or admin replies, they are assigned to that thread and the conversation continues in the same thread.
            </li>
          </ol>
          <dl class="grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-lg border p-3">
              <dt class="text-muted-foreground">Open threads</dt>
              <dd class="text-xl font-semibold">{{ openThreads }}</dd>
            </div>
            <div class="rounded-lg border p-3">
              <dt class="text-muted-foreground">Awaiting staff</dt>
              <dd class="text-xl font-semibold">{{ unassignedThreads }}</dd>
            </div>
          </dl>
          <Button as-child class="w-full sm:w-auto">
            <RouterLink to="/communications/threads">
              Open staff inbox
              <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="border-b border-border/60 pb-4">
          <CardTitle class="flex items-center gap-2 text-base">
            <Megaphone class="h-4 w-4 text-primary" aria-hidden="true" />
            School announcements
          </CardTitle>
          <CardDescription>
            One-way broadcasts to parents or the whole school — not threaded chat.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
          <p class="text-sm text-muted-foreground">
            Staff publish announcements from
            <RouterLink to="/communications/announcements" class="font-medium text-primary underline-offset-4 hover:underline">
              Announcements
            </RouterLink>.
            Parents see them in the portal feed; they cannot reply to an announcement directly.
          </p>
          <div class="rounded-lg border p-3">
            <p class="text-sm text-muted-foreground">Active announcements</p>
            <p class="text-xl font-semibold">{{ activeAnnouncements }}</p>
          </div>
          <Button variant="outline" as-child class="w-full sm:w-auto">
            <RouterLink to="/communications/announcements">
              Manage announcements
              <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
            </RouterLink>
          </Button>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader class="border-b border-border/60 pb-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <CardTitle class="text-base font-semibold">Recent message threads</CardTitle>
            <CardDescription>Latest parent conversations — reply from the inbox</CardDescription>
          </div>
          <Users class="h-5 w-5 text-muted-foreground" aria-hidden="true" />
        </div>
      </CardHeader>
      <CardContent class="pt-6">
        <div v-if="recentThreads.length" class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Subject</TableHead>
                <TableHead>From</TableHead>
                <TableHead>Assigned to</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Last activity</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="thread in recentThreads" :key="thread.id">
                <TableCell class="font-medium">
                  <RouterLink
                    :to="`/communications/threads`"
                    class="hover:text-primary hover:underline"
                  >
                    {{ thread.subject ?? 'No subject' }}
                  </RouterLink>
                </TableCell>
                <TableCell>{{ threadContact(thread) }}</TableCell>
                <TableCell>
                  <Badge :variant="thread.staff_user_id ? 'secondary' : 'outline'">
                    {{ threadAssignee(thread) }}
                  </Badge>
                </TableCell>
                <TableCell>
                  <Badge variant="outline" class="capitalize">{{ thread.status ?? 'open' }}</Badge>
                </TableCell>
                <TableCell class="text-right text-muted-foreground">
                  {{ thread.last_message_at ? formatDateTime(thread.last_message_at) : '—' }}
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
        <EmptyState
          v-else
          title="No message threads yet"
          description="Parents start conversations from the portal. Demo data includes a sample thread after seeding."
        >
          <Button as-child class="mt-2" variant="outline">
            <RouterLink to="/communications/threads">Open inbox</RouterLink>
          </Button>
        </EmptyState>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/60 pb-4">
        <CardTitle class="text-base font-semibold">Recent announcements</CardTitle>
        <CardDescription>Latest broadcasts published to parents</CardDescription>
      </CardHeader>
      <CardContent class="pt-6">
        <ul v-if="recentAnnouncements.length" class="divide-y">
          <li
            v-for="item in recentAnnouncements"
            :key="item.id"
            class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0"
          >
            <div>
              <p class="font-medium">{{ item.title ?? 'Untitled' }}</p>
              <p class="text-xs text-muted-foreground capitalize">
                {{ audienceLabel(item.target_audience ?? item.audience) }} · {{ item.is_active === false ? 'hidden' : 'visible' }}
              </p>
            </div>
            <Badge variant="outline">
              {{ item.date ? formatDateTime(item.date) : '—' }}
            </Badge>
          </li>
        </ul>
        <EmptyState
          v-else
          title="No announcements yet"
          description="Publish a school-wide or class announcement for parents to read in the portal."
        >
          <Button as-child class="mt-2" variant="outline">
            <RouterLink to="/communications/announcements">Create announcement</RouterLink>
          </Button>
        </EmptyState>
      </CardContent>
    </Card>
  </div>
</template>
