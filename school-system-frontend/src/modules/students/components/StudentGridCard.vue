<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Mail, MoreVertical, Phone, UserRound } from '@lucide/vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Checkbox } from '@/components/ui/checkbox'
import { formatMoney } from '@/lib/finance-constants'
import { resolveMediaUrl } from '@/lib/media-url'
import { cn } from '@/lib/utils'

const props = defineProps<{
  student: Record<string, unknown>
  currency?: string | null
  selected?: boolean
}>()

const emit = defineEmits<{
  'update:selected': [value: boolean]
}>()

function relationName(value: unknown): string {
  if (!value || typeof value !== 'object') return ''
  return String((value as Record<string, unknown>).name ?? '').trim()
}

function personInitials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'S'
}

function studentAge(dateOfBirth: unknown): string {
  if (!dateOfBirth) return '—'
  const birth = new Date(String(dateOfBirth))
  if (Number.isNaN(birth.getTime())) return '—'
  const today = new Date()
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age -= 1
  return age >= 0 && age < 120 ? String(age) : '—'
}

function primaryGuardian(row: Record<string, unknown>) {
  const guardians = row.guardians
  if (Array.isArray(guardians) && guardians.length) {
    const primary = guardians.find((item) => {
      if (!item || typeof item !== 'object') return false
      const pivot = (item as Record<string, unknown>).pivot
      return pivot && typeof pivot === 'object' && (pivot as Record<string, unknown>).is_primary
    }) ?? guardians[0]
    return primary as Record<string, unknown>
  }
  const nested = row.guardian
  if (nested && typeof nested === 'object') return nested as Record<string, unknown>
  return null
}

const id = computed(() => props.student.id)
const admissionId = computed(() =>
  String(props.student.student_number ?? props.student.studentNumber ?? `ID-${id.value ?? ''}`),
)
const name = computed(() =>
  String(
    props.student.full_name
    ?? props.student.fullName
    ?? `${props.student.first_name ?? ''} ${props.student.last_name ?? ''}`.trim(),
  ).trim() || 'Student',
)
const photo = computed(() => {
  const raw = String(props.student.photo_url ?? props.student.photoUrl ?? '').trim()
  return resolveMediaUrl(raw)
})
const status = computed(() => String(props.student.status ?? 'active').toLowerCase())

const classLabel = computed(() => {
  const classModel = props.student.class_model ?? props.student.classModel
  const className = relationName(classModel) || String(props.student.class ?? '').trim()
  const stream = relationName(props.student.stream)
  const house = relationName(props.student.house)
  const parts = [className, stream].filter(Boolean)
  if (house) parts.push(house)
  return parts.join(' · ') || 'Unassigned class'
})

const category = computed(() =>
  relationName(props.student.student_category ?? props.student.studentCategory),
)

const roll = computed(() => String(props.student.roll_number ?? props.student.rollNumber ?? '—'))
const age = computed(() => studentAge(props.student.date_of_birth ?? props.student.dateOfBirth))

const guardian = computed(() => primaryGuardian(props.student))
const guardianName = computed(() => {
  const g = guardian.value
  if (!g) return 'No guardian linked'
  const fromParts = `${g.first_name ?? ''} ${g.last_name ?? ''}`.trim()
  return String(g.full_name ?? (fromParts || 'Guardian'))
})
const guardianPhone = computed(() => String(guardian.value?.phone ?? '').trim())

const balance = computed(() => Number(props.student.balance ?? 0))
const feesPaid = computed(() => balance.value <= 0)
const phone = computed(() => String(props.student.phone ?? guardianPhone.value).trim())
const email = computed(() => String(props.student.email ?? guardian.value?.email ?? '').trim())

const statusTone = computed(() => {
  if (status.value === 'active') return 'text-chart-2'
  if (status.value === 'suspended' || status.value === 'inactive') return 'text-destructive'
  if (status.value === 'graduated') return 'text-primary'
  return 'text-muted-foreground'
})

const statusDotClass = computed(() => {
  if (status.value === 'active') return 'bg-chart-2'
  if (status.value === 'suspended' || status.value === 'inactive') return 'bg-destructive'
  if (status.value === 'graduated') return 'bg-primary'
  return 'bg-muted-foreground'
})
</script>

<template>
  <Card
    class="group overflow-hidden rounded-2xl border-0 py-0 shadow-[var(--shadow-soft)] ring-1 ring-border/70 transition-all duration-200 hover:-translate-y-0.5 hover:ring-primary/20 dark:bg-card dark:ring-border/50"
  >
    <CardHeader class="flex flex-row items-start justify-between gap-2 space-y-0 px-4 pb-0 pt-4">
      <div class="flex min-w-0 items-center gap-2">
        <Checkbox
          :model-value="selected"
          :aria-label="`Select ${name}`"
          @update:model-value="emit('update:selected', Boolean($event))"
        />
        <p class="truncate text-sm font-medium tracking-wide text-muted-foreground">
          {{ admissionId }}
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-1.5">
        <span
          class="inline-flex items-center gap-1.5 text-sm font-medium capitalize"
          :class="statusTone"
        >
          <span class="size-2 rounded-full" :class="statusDotClass" aria-hidden="true" />
          {{ status }}
        </span>
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              class="size-8 text-muted-foreground hover:text-foreground"
              :aria-label="`Actions for ${name}`"
            >
              <MoreVertical class="size-4" aria-hidden="true" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-44 rounded-xl">
            <DropdownMenuItem as-child class="cursor-pointer">
              <RouterLink :to="{ name: 'student-detail', params: { id: String(id) } }">
                View profile
              </RouterLink>
            </DropdownMenuItem>
            <DropdownMenuItem as-child class="cursor-pointer">
              <RouterLink :to="{ name: 'student-detail', params: { id: String(id) }, query: { tab: 'fees' } }">
                Fees
              </RouterLink>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </CardHeader>

    <CardContent class="space-y-4 px-4 py-4">
      <div class="flex items-start gap-3">
        <Avatar class="size-14 shrink-0 rounded-full ring-2 ring-background shadow-sm">
          <AvatarImage v-if="photo" :src="photo" :alt="`${name} profile photo`" />
          <AvatarFallback class="bg-accent text-sm font-semibold text-primary">
            {{ personInitials(name) }}
          </AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1 space-y-1.5">
          <p class="truncate text-lg font-medium tracking-tight text-foreground">{{ name }}</p>
          <p class="truncate text-sm text-muted-foreground">{{ classLabel }}</p>
          <div v-if="category" class="flex flex-wrap gap-1">
            <Badge
              variant="secondary"
              class="rounded-full border-0 bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
              {{ category }}
            </Badge>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-3 divide-x divide-border/70 overflow-hidden rounded-xl bg-muted/35 ring-1 ring-border/50 dark:bg-muted/50 dark:divide-border/40">
        <div class="px-2 py-2.5 text-center">
          <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Roll</p>
          <p class="mt-1 text-base font-semibold tabular-nums text-foreground">{{ roll }}</p>
        </div>
        <div class="px-2 py-2.5 text-center">
          <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Rank</p>
          <p class="mt-1 text-base font-semibold tabular-nums text-foreground">—</p>
        </div>
        <div class="px-2 py-2.5 text-center">
          <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Age</p>
          <p class="mt-1 text-base font-semibold tabular-nums text-foreground">{{ age }}</p>
        </div>
      </div>

      <div class="flex items-start gap-2.5 rounded-xl bg-muted/40 px-3 py-2.5 ring-1 ring-border/40 dark:bg-muted/55">
        <span
          class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-background text-muted-foreground ring-1 ring-border/60"
          aria-hidden="true"
        >
          <UserRound class="size-3.5" />
        </span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-base font-medium text-foreground">{{ guardianName }}</p>
          <p class="truncate text-sm text-muted-foreground">
            {{ guardianPhone || 'No phone on file' }}
          </p>
        </div>
      </div>

      <div class="flex flex-wrap gap-1.5">
        <Badge
          :class="cn(
            'rounded-full border-0 px-2.5 py-0.5 text-xs font-semibold',
            feesPaid
              ? 'bg-chart-2/15 text-chart-2'
              : 'bg-destructive/10 text-destructive',
          )"
        >
          {{ feesPaid ? 'Fees Paid' : `Due ${formatMoney(balance, currency ?? undefined)}` }}
        </Badge>
      </div>
    </CardContent>

    <CardFooter class="flex items-center justify-between gap-2 border-t border-border/50 bg-muted/20 px-4 py-3 dark:bg-muted/30 dark:border-border/40">
      <div class="flex items-center gap-0.5">
        <Button
          v-if="phone"
          variant="ghost"
          size="icon"
          class="size-8 text-muted-foreground hover:text-foreground"
          as-child
        >
          <a :href="`tel:${phone}`" :aria-label="`Call ${name}`">
            <Phone class="size-4" aria-hidden="true" />
          </a>
        </Button>
        <Button
          v-if="email"
          variant="ghost"
          size="icon"
          class="size-8 text-muted-foreground hover:text-foreground"
          as-child
        >
          <a :href="`mailto:${email}`" :aria-label="`Email ${name}`">
            <Mail class="size-4" aria-hidden="true" />
          </a>
        </Button>
      </div>
      <Button
        as-child
        size="sm"
        variant="outline"
        class="h-9 rounded-xl border-primary/35 px-3.5 text-sm font-medium text-primary hover:bg-accent hover:text-primary"
      >
        <RouterLink :to="{ name: 'student-detail', params: { id: String(id) } }">
          Quick View
        </RouterLink>
      </Button>
    </CardFooter>
  </Card>
</template>
