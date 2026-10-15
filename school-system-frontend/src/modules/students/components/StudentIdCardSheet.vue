<script setup lang="ts">
import { computed } from 'vue'
import { GraduationCap, MapPin, Printer } from '@lucide/vue'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/format'
import { resolveMediaUrl } from '@/lib/media-url'

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  document?: Record<string, unknown> | null
  documents?: Record<string, unknown>[]
  loading?: boolean
}>()

const cards = computed(() => {
  if (props.documents?.length) return props.documents
  return props.document ? [props.document] : []
})

const title = computed(() =>
  cards.value.length > 1 ? `Student ID cards (${cards.value.length})` : 'Student ID card',
)

function studentOf(card: Record<string, unknown>) {
  return (card.student as Record<string, unknown> | undefined) ?? {}
}

function schoolOf(card: Record<string, unknown>) {
  return (card.school as Record<string, unknown> | undefined) ?? {}
}

function initialsOf(card: Record<string, unknown>) {
  const name = String(studentOf(card).full_name ?? '')
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'ST'
  return parts.slice(0, 2).map((part) => part[0]?.toUpperCase() ?? '').join('')
}

function primaryColor(card: Record<string, unknown>) {
  return String(schoolOf(card).primary_color ?? '#3D5EE1')
}

function photoUrl(card: Record<string, unknown>) {
  return resolveMediaUrl(String(studentOf(card).photo_url ?? '')) ?? studentOf(card).photo_url
}

function admissionNo(card: Record<string, unknown>) {
  return String(studentOf(card).student_number ?? card.document_number ?? '—')
}

function spacedAdmission(card: Record<string, unknown>) {
  return admissionNo(card).replace(/\s+/g, '').split('').join(' ')
}

function validUntil(card: Record<string, unknown>) {
  const nested = card.card as Record<string, unknown> | undefined
  const fromCard = card.valid_until ?? nested?.valid_until
  if (fromCard) return formatDate(String(fromCard))
  const issued = card.issued_at ? new Date(String(card.issued_at)) : new Date()
  if (Number.isNaN(issued.getTime())) return '—'
  const end = new Date(issued)
  end.setFullYear(end.getFullYear() + 1)
  return formatDate(end.toISOString())
}

function bloodGroup(card: Record<string, unknown>) {
  const value = studentOf(card).blood_group ?? studentOf(card).blood_type
  return value ? String(value) : null
}

function printCard() {
  document.body.classList.add('printing-id-card')
  const cleanup = () => {
    document.body.classList.remove('printing-id-card')
    window.removeEventListener('afterprint', cleanup)
  }
  window.addEventListener('afterprint', cleanup)
  window.setTimeout(cleanup, 1500)
  window.print()
}
</script>

<template>
  <Sheet v-model:open="open">
    <SheetContent
      class="flex w-full flex-col gap-0 overflow-y-auto p-0 sm:max-w-md print:max-w-none"
      side="right"
    >
      <SheetHeader class="border-b border-border/60 px-5 py-4 print:hidden">
        <div class="flex items-start justify-between gap-3 pr-8">
          <div class="space-y-1">
            <SheetTitle class="flex items-center gap-2 text-base">
              <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                <GraduationCap class="size-4" />
              </span>
              {{ title }}
            </SheetTitle>
            <SheetDescription>Preview and print a wallet-sized student identity card.</SheetDescription>
          </div>
        </div>
      </SheetHeader>

      <div class="flex-1 bg-muted/30 px-5 py-6 print:bg-white print:p-0">
        <div v-if="loading" class="py-8 text-sm text-muted-foreground print:hidden" role="status">
          Loading ID card…
        </div>

        <div
          v-else-if="cards.length"
          class="mx-auto space-y-6 print:mt-0 print:space-y-0"
          data-student-id-card
        >
          <article
            v-for="(card, index) in cards"
            :key="String(card.document_number ?? studentOf(card).id ?? index)"
            class="mx-auto w-full max-w-[22rem] overflow-hidden rounded-[1.25rem] bg-card shadow-[var(--shadow-lift)] ring-1 ring-border/60 print:break-after-page print:shadow-none"
            :aria-label="`Student ID card for ${String(studentOf(card).full_name ?? 'student')}`"
          >
            <!-- Curved header -->
            <div
              class="relative px-5 pb-10 pt-5 text-primary-foreground"
              :style="{ backgroundColor: primaryColor(card) }"
            >
              <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
                  <GraduationCap class="size-5" />
                </div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold uppercase tracking-wide">
                    {{ schoolOf(card).name ?? 'School' }}
                  </p>
                  <p class="truncate text-[11px] uppercase tracking-wider text-white/80">
                    {{ schoolOf(card).motto || 'Excellence in education' }}
                  </p>
                </div>
              </div>
              <div
                class="absolute -bottom-5 left-0 right-0 h-10 bg-card"
                style="border-radius: 50% 50% 0 0 / 100% 100% 0 0"
                aria-hidden="true"
              />
            </div>

            <!-- Photo + name -->
            <div class="relative z-10 -mt-2 space-y-3 px-5 pb-4 text-center">
              <div
                class="mx-auto flex size-24 items-center justify-center overflow-hidden rounded-2xl border-4 border-card bg-muted text-2xl font-semibold text-muted-foreground shadow-sm"
              >
                <img
                  v-if="photoUrl(card)"
                  :src="String(photoUrl(card))"
                  :alt="`${String(studentOf(card).full_name ?? 'Student')} photo`"
                  class="size-full object-cover"
                />
                <span v-else>{{ initialsOf(card) }}</span>
              </div>
              <div class="space-y-1.5">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">
                  {{ studentOf(card).full_name ?? 'Student' }}
                </h3>
                <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary">
                  Student
                </span>
              </div>
            </div>

            <!-- Details -->
            <dl class="divide-y divide-border/60 px-5 text-sm">
              <div class="flex items-center justify-between gap-3 py-2.5">
                <dt class="text-muted-foreground">Admission No</dt>
                <dd class="font-semibold tabular-nums">{{ admissionNo(card) }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3 py-2.5">
                <dt class="text-muted-foreground">Class</dt>
                <dd class="font-semibold">{{ studentOf(card).class ?? '—' }}</dd>
              </div>
              <div class="flex items-center justify-between gap-3 py-2.5">
                <dt class="text-muted-foreground">Date of Birth</dt>
                <dd class="font-semibold">{{ formatDate(studentOf(card).date_of_birth) }}</dd>
              </div>
              <div v-if="bloodGroup(card)" class="flex items-center justify-between gap-3 py-2.5">
                <dt class="text-muted-foreground">Blood Group</dt>
                <dd class="font-semibold text-destructive">{{ bloodGroup(card) }}</dd>
              </div>
            </dl>

            <!-- Barcode -->
            <div class="space-y-2 px-5 py-4">
              <div
                class="mx-auto flex h-12 w-full max-w-[14rem] items-end justify-between gap-px px-1"
                aria-hidden="true"
              >
                <span
                  v-for="n in 28"
                  :key="n"
                  class="bg-foreground"
                  :style="{ width: n % 3 === 0 ? '3px' : '2px', height: `${40 + ((n * 7) % 40)}%` }"
                />
              </div>
              <p class="text-center font-mono text-[11px] tracking-[0.2em] text-muted-foreground">
                {{ spacedAdmission(card) }}
              </p>
            </div>

            <!-- Footer -->
            <div
              class="flex items-center justify-between gap-2 px-4 py-3 text-[11px] text-primary-foreground"
              :style="{ backgroundColor: primaryColor(card) }"
            >
              <p class="flex min-w-0 items-center gap-1.5 truncate">
                <MapPin class="size-3.5 shrink-0 opacity-90" aria-hidden="true" />
                <span class="truncate">{{ schoolOf(card).address || 'School campus' }}</span>
              </p>
              <p class="shrink-0 opacity-90">Valid till {{ validUntil(card) }}</p>
            </div>
          </article>
        </div>

        <p v-else class="py-8 text-center text-sm text-muted-foreground print:hidden">
          No ID card data available.
        </p>
      </div>

      <SheetFooter class="mt-0 gap-2 border-t border-border/60 bg-card px-5 py-4 print:hidden sm:flex-row sm:justify-end">
        <Button type="button" variant="secondary" class="rounded-xl" @click="open = false">
          Close
        </Button>
        <Button type="button" class="rounded-xl" :disabled="!cards.length" @click="printCard">
          <Printer class="mr-2 size-4" aria-hidden="true" />
          {{ cards.length > 1 ? 'Print IDs' : 'Print ID' }}
        </Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>
