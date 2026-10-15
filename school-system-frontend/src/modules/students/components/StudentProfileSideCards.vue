<script setup lang="ts">
import { computed } from 'vue'
import { Bus, Home, Mail, Phone, School, UserRound } from '@lucide/vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { STATUS_TONE_ICON } from '@/lib/ui-status'
import { cn } from '@/lib/utils'

type GuardianRow = {
  id?: number
  first_name?: string
  last_name?: string
  full_name?: string
  phone?: string
  email?: string
  relationship?: string
  occupation?: string
  photo_url?: string | null
  pivot?: { relationship?: string; is_primary?: boolean; emergency_contact?: boolean }
}

const props = defineProps<{
  guardians: GuardianRow[]
  transport?: Record<string, unknown> | null
  hostel?: Record<string, unknown> | null
  class?: string
}>()

function guardianName(g: GuardianRow) {
  const fromParts = [g.first_name, g.last_name].filter(Boolean).join(' ')
  return g.full_name ?? (fromParts || 'Guardian')
}

function guardianInitials(g: GuardianRow) {
  return guardianName(g)
    .split(' ')
    .map((p) => p[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'G'
}

function relationLabel(g: GuardianRow) {
  return g.pivot?.relationship ?? g.relationship ?? 'Guardian'
}

const routeName = computed(() => {
  const t = props.transport
  if (!t) return null
  const route = t.route as Record<string, unknown> | undefined
  return String(route?.name ?? route?.code ?? '').trim() || null
})

const busNumber = computed(() => {
  const t = props.transport
  if (!t) return null
  const route = t.route as Record<string, unknown> | undefined
  const vehicle = route?.vehicle as Record<string, unknown> | undefined
  return String(vehicle?.registration_number ?? vehicle?.name ?? vehicle?.plate_number ?? '').trim() || null
})

const driverName = computed(() => {
  const t = props.transport
  if (!t) return null
  const route = t.route as Record<string, unknown> | undefined
  const driver = route?.driver as Record<string, unknown> | undefined
  if (!driver) return null
  return String(driver.name ?? driver.full_name ?? [driver.first_name, driver.last_name].filter(Boolean).join(' ')).trim() || null
})

const pickupPoint = computed(() => {
  const t = props.transport
  if (!t) return null
  return String(t.pickup_point ?? t.pickup ?? '').trim() || null
})

const dropPoint = computed(() => {
  const t = props.transport
  if (!t) return null
  return String(t.drop_point ?? t.dropoff ?? '').trim() || null
})

const hostelName = computed(() => {
  const h = props.hostel
  if (!h) return null
  const bed = h.bed as Record<string, unknown> | undefined
  const room = bed?.room as Record<string, unknown> | undefined
  const hostel = room?.hostel as Record<string, unknown> | undefined
  return String(hostel?.name ?? '').trim() || null
})

const roomLabel = computed(() => {
  const h = props.hostel
  if (!h) return null
  const bed = h.bed as Record<string, unknown> | undefined
  const room = bed?.room as Record<string, unknown> | undefined
  const roomName = String(room?.name ?? room?.number ?? '').trim()
  const bedName = String(bed?.label ?? bed?.name ?? bed?.number ?? '').trim()
  return [roomName, bedName].filter(Boolean).join(' · ') || null
})

const blockLabel = computed(() => {
  const h = props.hostel
  if (!h) return null
  const bed = h.bed as Record<string, unknown> | undefined
  const room = bed?.room as Record<string, unknown> | undefined
  return String(room?.block ?? room?.wing ?? '').trim() || null
})
</script>

<template>
  <div :class="cn('space-y-4', $props.class)">
    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <UserRound class="size-4 text-primary" aria-hidden="true" />
          Parents & Guardians
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-3 p-4">
        <p v-if="!guardians.length" class="px-1 py-4 text-center text-sm text-muted-foreground">
          No guardian linked yet.
        </p>
        <article
          v-for="guardian in guardians"
          :key="String(guardian.id ?? guardianName(guardian))"
          class="flex gap-3 rounded-2xl bg-muted/35 p-3 ring-1 ring-border/40"
        >
          <Avatar class="size-12 shrink-0 rounded-xl">
            <AvatarImage
              v-if="guardian.photo_url"
              :src="guardian.photo_url"
              :alt="guardianName(guardian)"
              class="rounded-xl object-cover"
            />
            <AvatarFallback class="rounded-xl bg-primary/10 text-sm font-semibold text-primary">
              {{ guardianInitials(guardian) }}
            </AvatarFallback>
          </Avatar>
          <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="truncate text-sm font-semibold text-foreground">{{ guardianName(guardian) }}</p>
              <Badge
                v-if="guardian.pivot?.is_primary"
                variant="secondary"
                class="h-5 text-[10px]"
              >
                Primary
              </Badge>
            </div>
            <p class="text-xs capitalize text-muted-foreground">
              {{ relationLabel(guardian) }}
              <template v-if="guardian.occupation"> · {{ guardian.occupation }}</template>
            </p>
            <div class="flex flex-wrap gap-1.5 pt-1">
              <Button
                v-if="guardian.phone"
                variant="outline"
                size="icon"
                class="size-8 rounded-full"
                as-child
              >
                <a :href="`tel:${guardian.phone}`" :aria-label="`Call ${guardianName(guardian)}`">
                  <Phone class="size-3.5" />
                </a>
              </Button>
              <Button
                v-if="guardian.email"
                variant="outline"
                size="icon"
                class="size-8 rounded-full"
                as-child
              >
                <a :href="`mailto:${guardian.email}`" :aria-label="`Email ${guardianName(guardian)}`">
                  <Mail class="size-3.5" />
                </a>
              </Button>
            </div>
            <p v-if="guardian.phone" class="truncate text-xs text-muted-foreground">{{ guardian.phone }}</p>
          </div>
        </article>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <div class="flex items-center justify-between gap-2">
          <CardTitle class="flex items-center gap-2 text-base">
            <span class="flex size-8 items-center justify-center rounded-xl bg-primary/10 text-primary" aria-hidden="true">
              <Bus class="size-4" />
            </span>
            Transport
          </CardTitle>
          <Badge v-if="transport" variant="success" class="h-5 text-[10px]">Allocated</Badge>
        </div>
      </CardHeader>
      <CardContent class="space-y-4 p-5">
        <template v-if="transport && routeName">
          <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt class="text-xs text-muted-foreground">Bus / vehicle</dt>
              <dd class="mt-0.5 font-medium">{{ busNumber ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground">Route</dt>
              <dd class="mt-0.5 font-medium">{{ routeName }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground">Driver</dt>
              <dd class="mt-0.5 font-medium">{{ driverName ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground">Status</dt>
              <dd class="mt-0.5 font-medium capitalize">{{ String(transport.status ?? 'active') }}</dd>
            </div>
            <div v-if="pickupPoint" class="sm:col-span-2">
              <dt class="text-xs text-muted-foreground">Pickup</dt>
              <dd class="mt-0.5 font-medium">{{ pickupPoint }}</dd>
            </div>
            <div v-if="dropPoint" class="sm:col-span-2">
              <dt class="text-xs text-muted-foreground">Drop</dt>
              <dd class="mt-0.5 font-medium">{{ dropPoint }}</dd>
            </div>
          </dl>
          <div
            class="flex items-center gap-3 rounded-xl bg-muted/40 px-3 py-3 ring-1 ring-border/40"
            aria-hidden="true"
          >
            <Bus class="size-4 shrink-0 text-primary" />
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
              <div class="h-full w-2/3 rounded-full bg-primary" />
            </div>
            <School class="size-4 shrink-0 text-chart-2" />
          </div>
        </template>
        <p v-else class="text-sm text-muted-foreground">
          No transport route allocated. Assign one from Journey → Transport.
        </p>
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="flex items-center gap-2 text-base">
          <span
            :class="cn('flex size-8 items-center justify-center rounded-xl', STATUS_TONE_ICON.accent)"
            aria-hidden="true"
          >
            <Home class="size-4" />
          </span>
          Hostel
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-3 p-5">
        <template v-if="hostel && (hostelName || roomLabel)">
          <dl class="grid gap-3 text-sm">
            <div>
              <dt class="text-xs text-muted-foreground">Hostel</dt>
              <dd class="mt-0.5 font-medium">{{ hostelName ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs text-muted-foreground">Room / bed</dt>
              <dd class="mt-0.5 font-medium">{{ roomLabel ?? '—' }}</dd>
            </div>
            <div v-if="blockLabel">
              <dt class="text-xs text-muted-foreground">Block</dt>
              <dd class="mt-0.5 font-medium">{{ blockLabel }}</dd>
            </div>
          </dl>
        </template>
        <p v-else class="text-sm text-muted-foreground">
          Not allocated — assign a bed under Operations → Hostels.
        </p>
      </CardContent>
    </Card>
  </div>
</template>
