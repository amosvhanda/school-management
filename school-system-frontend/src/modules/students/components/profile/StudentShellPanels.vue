<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { Award, History, Library, Lock, MessageSquare, ScrollText } from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { complianceApi, operationsApi } from '@/services/api.service'
import { formatDateTime } from '@/lib/format'
import { STATUS_TONE_BADGE, STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

const props = defineProps<{
  studentId: string | number
  studentName?: string
  panel: 'library' | 'achievements' | 'communication' | 'notes' | 'audit'
}>()

const loading = ref(false)
const rows = ref<Record<string, unknown>[]>([])

async function load() {
  loading.value = true
  rows.value = []
  try {
    if (props.panel === 'library') {
      const members = (await operationsApi.library.members.list({
        filter: { student_id: props.studentId },
        per_page: 20,
      } as never)) as Record<string, unknown>[]
      rows.value = Array.isArray(members) ? members : []
    } else if (props.panel === 'audit') {
      try {
        const list = await complianceApi.auditLogs.list({
          filter: { subject_id: props.studentId },
          per_page: 30,
        } as never)
        rows.value = (Array.isArray(list) ? list : []) as unknown as Record<string, unknown>[]
      } catch {
        rows.value = []
      }
    }
  } catch {
    rows.value = []
  } finally {
    loading.value = false
  }
}

watch(() => [props.studentId, props.panel] as const, load)
onMounted(load)
</script>

<template>
  <Card v-if="panel === 'library'">
    <CardHeader class="border-b border-border/50 px-5 py-4">
      <CardTitle class="flex items-center gap-2 text-base">
        <Library class="size-4 text-primary" aria-hidden="true" />
        Library
      </CardTitle>
    </CardHeader>
    <CardContent class="p-5">
      <PageLoader v-if="loading" label="Loading library" />
      <ul v-else-if="rows.length" class="space-y-3">
        <li
          v-for="(row, i) in rows"
          :key="String(row.id ?? i)"
          class="flex items-center justify-between gap-3 rounded-xl border border-border/60 px-4 py-3"
        >
          <div>
            <p class="text-sm font-semibold">{{ row.membership_number ?? row.name ?? 'Library member' }}</p>
            <p class="text-xs capitalize text-muted-foreground">{{ row.status ?? 'Active' }}</p>
          </div>
          <Badge variant="secondary" class="font-normal">Member</Badge>
        </li>
      </ul>
      <EmptyState
        v-else
        variant="embedded"
        title="No library activity"
        description="Borrowed books and membership details will appear when linked."
      />
    </CardContent>
  </Card>

  <div v-else-if="panel === 'achievements'" class="space-y-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <Card
        v-for="stat in [
          { label: 'Total awards', value: '—', tone: STATUS_TONE_ICON.warning },
          { label: 'Gold medals', value: '—', tone: STATUS_TONE_ICON.danger },
          { label: 'Certificates', value: '—', tone: STATUS_TONE_ICON.info },
          { label: 'Badges', value: '—', tone: STATUS_TONE_ICON.accent },
        ]"
        :key="stat.label"
        class="border-l-4 border-l-primary/40"
      >
        <CardContent class="flex items-center gap-3 p-4">
          <div :class="cn('flex size-10 items-center justify-center rounded-xl', stat.tone)">
            <Award class="size-5" aria-hidden="true" />
          </div>
          <div>
            <p class="text-2xl font-semibold">{{ stat.value }}</p>
            <p class="text-xs text-muted-foreground">{{ stat.label }}</p>
          </div>
        </CardContent>
      </Card>
    </div>
    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <Award class="size-4 text-primary" aria-hidden="true" />
          Awards & honors
        </CardTitle>
      </CardHeader>
      <CardContent class="p-5">
        <EmptyState
          variant="embedded"
          title="No achievements recorded"
          description="Olympiads, sports medals, and certificates will display when captured."
        />
      </CardContent>
    </Card>
  </div>

  <Card v-else-if="panel === 'communication'">
    <CardHeader class="border-b border-border/50 px-5 py-4">
      <CardTitle class="flex items-center gap-2 text-base">
        <MessageSquare class="size-4 text-primary" aria-hidden="true" />
        Communication log
      </CardTitle>
    </CardHeader>
    <CardContent class="p-5">
      <EmptyState
        variant="embedded"
        title="No interactions logged"
        description="Emails, SMS, calls, and meetings with guardians will appear in this log."
      />
    </CardContent>
  </Card>

  <Card v-else-if="panel === 'notes'">
    <CardContent class="flex flex-col items-center gap-4 p-10 text-center">
      <div :class="cn('flex size-14 items-center justify-center rounded-2xl', STATUS_TONE_ICON.neutral)">
        <Lock class="size-6" aria-hidden="true" />
      </div>
      <div class="space-y-1">
        <h3 class="text-base font-semibold">Private notes are locked</h3>
        <p class="max-w-md text-sm text-muted-foreground">
          Counsellor and staff notes for {{ studentName || 'this student' }} require additional permission.
        </p>
      </div>
      <Badge :class="cn('border', STATUS_TONE_BADGE.neutral)">Restricted</Badge>
    </CardContent>
  </Card>

  <div v-else class="space-y-6">
    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <ScrollText class="size-4 text-primary" aria-hidden="true" />
          Audit trail
        </CardTitle>
        <p class="text-sm text-muted-foreground">Profile change history</p>
      </CardHeader>
      <CardContent class="p-5">
        <PageLoader v-if="loading" label="Loading audit log" />
        <ul v-else-if="rows.length" class="space-y-4">
          <li
            v-for="(row, i) in rows"
            :key="String(row.id ?? i)"
            class="flex gap-3 border-b border-border/40 pb-4 last:border-0 last:pb-0"
          >
            <div :class="cn('flex size-9 shrink-0 items-center justify-center rounded-xl', STATUS_TONE_ICON.info)">
              <History class="size-4" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold">{{ row.action ?? row.event ?? 'Change recorded' }}</p>
                <Badge variant="outline" class="text-[10px] uppercase">
                  {{ row.category ?? row.auditable_type ?? 'Profile' }}
                </Badge>
              </div>
              <p class="text-sm text-muted-foreground">
                {{ row.description ?? row.summary ?? 'Profile update' }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ formatDateTime(String(row.created_at ?? row.timestamp ?? '')) }}
                <span v-if="row.user_name || row.actor"> · {{ row.user_name ?? row.actor }}</span>
              </p>
            </div>
          </li>
        </ul>
        <EmptyState
          v-else
          variant="embedded"
          title="No audit entries"
          description="Edits to this student profile will be listed here when audit logging is available."
        />
      </CardContent>
    </Card>
  </div>
</template>
