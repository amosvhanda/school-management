<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
// Corrected Lucide import path
import {
  Bell,
  CheckCircle2,
  CreditCard,
  FileText,
  Receipt,
  UserPlus,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { formatRelativeTime } from '@/lib/format'
import { useNotificationStore } from '@/stores/notification.store'

const notificationStore = useNotificationStore()

const iconMap: Record<string, typeof CreditCard> = {
  payment: CreditCard,
  invoice: Receipt,
  student: UserPlus,
}

const items = computed(() =>
  notificationStore.recentActivity.slice(0, 8).map((item) => ({
    ...item,
    time: formatRelativeTime(item.timestamp),
    Icon: iconMap[item.type] ?? FileText,
  })),
)

const badgeTotal = computed(() => {
  const n = notificationStore.workflowCount
  return n > 0 ? n : items.value.length > 0 ? items.value.length : 0
})
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        class="relative size-9"
        aria-label="Open notifications"
      >
        <Bell class="size-4" aria-hidden="true" />
        <span
          v-if="badgeTotal > 0"
          class="absolute -top-0.5 -right-0.5 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground animate-in zoom-in-50 duration-200"
          :aria-label="`${badgeTotal} unread notifications`"
        >
          {{ badgeTotal > 9 ? '9+' : badgeTotal }}
        </span>
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-80 p-0 rounded-xl shadow-lg border">
      <DropdownMenuLabel class="flex items-center justify-between px-4 py-3 text-sm font-semibold">
        <span>Notifications</span>
        <Badge v-if="notificationStore.workflowCount" variant="secondary" class="text-xs font-normal">
          {{ notificationStore.workflowCount }} pending
        </Badge>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <ScrollArea class="max-h-80">
        <div v-if="!items.length" class="flex flex-col items-center gap-2 px-4 py-8 text-center">
          <CheckCircle2 class="size-8 text-muted-foreground/40" aria-hidden="true" />
          <p class="text-sm font-medium text-foreground">You're all caught up</p>
          <p class="text-xs text-muted-foreground leading-normal max-w-[200px]">Recent activity will appear here.</p>
        </div>
        <div v-else class="divide-y divide-muted/60">
          <div
            v-for="item in items"
            :key="String(item.id)"
            class="flex gap-3 px-4 py-3 transition-colors hover:bg-muted/40"
          >
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <component :is="item.Icon" class="size-3.5" aria-hidden="true" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium text-foreground leading-snug">{{ item.description }}</p>
              <p class="mt-0.5 text-xs text-muted-foreground truncate">{{ item.user }} · {{ item.time }}</p>
            </div>
          </div>
        </div>
      </ScrollArea>
      <DropdownMenuSeparator />
      <div class="p-2 bg-muted/5">
        <Button variant="ghost" size="sm" class="w-full justify-center h-8 text-xs font-medium" as-child>
          <RouterLink to="/">View dashboard activity</RouterLink>
        </Button>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
