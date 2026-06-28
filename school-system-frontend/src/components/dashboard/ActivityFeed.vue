<script setup lang="ts">
import { computed } from 'vue'
import {
  CreditCard,
  FileText,
  Receipt,
  UserPlus,
} from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { formatRelativeTime } from '@/lib/format'
import type { RecentActivityItem } from '@/types/dashboard'

const props = defineProps<{ items: RecentActivityItem[]; class?: string }>()

const iconMap: Record<string, typeof CreditCard> = {
  payment: CreditCard,
  invoice: Receipt,
  student: UserPlus,
}

const statusVariant = (status: string) => {
  if (status === 'success') return 'default'
  if (status === 'warning') return 'secondary'
  return 'outline'
}

const formatted = computed(() =>
  props.items.map((item) => ({
    ...item,
    time: formatRelativeTime(item.timestamp),
    Icon: iconMap[item.type] ?? FileText,
  })),
)
</script>

<template>
  <Card :class="['bg-card text-card-foreground shadow-sm flex h-full flex-col', props.class]">
    <CardHeader class="border-b border-border/60 pb-4">
      <CardTitle class="text-base font-semibold tracking-tight">Recent activity</CardTitle>
      <CardDescription class="text-xs">Latest payments, invoices, and registrations</CardDescription>
    </CardHeader>
    <CardContent class="flex flex-1 flex-col p-0">
      <EmptyState
        v-if="!formatted.length"
        class="py-12"
        title="No activity yet"
        description="Transactions and registrations will appear here in real time."
      />
      <ScrollArea v-else class="flex-1">
        <div class="relative px-4 py-2">
          <div
            class="absolute top-4 bottom-4 left-[27px] w-px bg-border"
            aria-hidden="true"
          />
          <div
            v-for="item in formatted"
            :key="String(item.id)"
            class="relative flex gap-3 rounded-lg py-3 pl-1 transition-colors hover:bg-muted/40"
          >
            <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full border bg-background shadow-sm">
              <component :is="item.Icon" class="size-3.5 text-primary" />
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
              <p class="text-sm leading-snug font-medium text-foreground">{{ item.description }}</p>
              <p class="mt-0.5 text-xs text-muted-foreground">{{ item.user }}</p>
              <p class="text-[10px] text-muted-foreground/70">{{ item.time }}</p>
            </div>
            <Badge :variant="statusVariant(item.status)" class="mt-1 shrink-0 h-fit text-xs font-normal capitalize">
              {{ item.action }}
            </Badge>
          </div>
        </div>
      </ScrollArea>
    </CardContent>
  </Card>
</template>
