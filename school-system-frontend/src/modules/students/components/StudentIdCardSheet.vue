<script setup lang="ts">
import { computed } from 'vue'
import { Printer } from '@lucide/vue'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { formatDate } from '@/lib/format'

const props = defineProps<{
  open: boolean
  document?: Record<string, unknown> | null
  documents?: Record<string, unknown>[]
  loading?: boolean
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

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

function accentStyle(card: Record<string, unknown>) {
  const color = String(schoolOf(card).primary_color ?? '#FF7A00')
  return { borderColor: color, color }
}

function printCard() {
  window.print()
}
</script>

<template>
  <Sheet :open="open" @update:open="$emit('update:open', $event)">
    <SheetContent class="w-full overflow-y-auto sm:max-w-md print:max-w-none" side="right">
      <SheetHeader class="print:hidden">
        <SheetTitle>{{ title }}</SheetTitle>
        <SheetDescription>Printable student identification card</SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="py-8 text-sm text-muted-foreground">Loading ID card…</div>

      <div v-else-if="cards.length" class="mt-6 space-y-6 print:mt-0 print:space-y-0">
        <article
          v-for="(card, index) in cards"
          :key="String(card.document_number ?? studentOf(card).id ?? index)"
          class="space-y-4 print:break-after-page"
          :aria-label="`Student ID card for ${String(studentOf(card).full_name ?? 'student')}`"
        >
          <div
            class="mx-auto w-full max-w-sm overflow-hidden rounded-2xl border-2 bg-card shadow-sm print:shadow-none"
            :style="{ borderColor: String(schoolOf(card).primary_color ?? '#FF7A00') }"
          >
            <div
              class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-white"
              :style="{ backgroundColor: String(schoolOf(card).primary_color ?? '#FF7A00') }"
            >
              Student ID
            </div>

            <div class="space-y-4 p-5">
              <div class="text-center space-y-1">
                <h2 class="text-lg font-semibold leading-tight">{{ schoolOf(card).name ?? 'School' }}</h2>
                <p v-if="schoolOf(card).motto" class="text-xs text-muted-foreground">{{ schoolOf(card).motto }}</p>
              </div>

              <div class="flex items-center gap-4">
                <div
                  class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted text-xl font-semibold"
                  :style="accentStyle(card)"
                  aria-hidden="true"
                >
                  <img
                    v-if="studentOf(card).photo_url"
                    :src="String(studentOf(card).photo_url)"
                    :alt="`${String(studentOf(card).full_name ?? 'Student')} photo`"
                    class="size-full object-cover"
                  />
                  <span v-else>{{ initialsOf(card) }}</span>
                </div>

                <dl class="min-w-0 flex-1 space-y-1 text-sm">
                  <div>
                    <dt class="sr-only">Student name</dt>
                    <dd class="font-semibold leading-tight">{{ studentOf(card).full_name ?? '—' }}</dd>
                  </div>
                  <div>
                    <dt class="text-xs text-muted-foreground">Student no.</dt>
                    <dd class="font-mono text-xs">{{ studentOf(card).student_number ?? '—' }}</dd>
                  </div>
                  <div>
                    <dt class="text-xs text-muted-foreground">Class</dt>
                    <dd>{{ studentOf(card).class ?? '—' }}</dd>
                  </div>
                  <div v-if="studentOf(card).grade_level">
                    <dt class="text-xs text-muted-foreground">Grade</dt>
                    <dd>{{ studentOf(card).grade_level }}</dd>
                  </div>
                </dl>
              </div>

              <dl class="grid grid-cols-2 gap-3 border-t pt-3 text-xs">
                <div v-if="studentOf(card).date_of_birth">
                  <dt class="text-muted-foreground">Date of birth</dt>
                  <dd>{{ formatDate(studentOf(card).date_of_birth) }}</dd>
                </div>
                <div v-if="studentOf(card).gender">
                  <dt class="text-muted-foreground">Gender</dt>
                  <dd class="capitalize">{{ studentOf(card).gender }}</dd>
                </div>
                <div v-if="studentOf(card).house" class="col-span-2">
                  <dt class="text-muted-foreground">House</dt>
                  <dd>{{ studentOf(card).house }}</dd>
                </div>
              </dl>

              <p class="border-t pt-3 text-center font-mono text-[10px] text-muted-foreground">
                {{ card.document_number }}
              </p>
            </div>
          </div>
        </article>

        <div class="flex justify-end print:hidden">
          <Button variant="outline" @click="printCard">
            <Printer class="mr-2 h-4 w-4" aria-hidden="true" />
            {{ cards.length > 1 ? 'Print ID cards' : 'Print ID card' }}
          </Button>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<style scoped>
@media print {
  :deep([data-slot='sheet-content']) {
    position: static !important;
    inset: auto !important;
    transform: none !important;
    width: 100% !important;
    max-width: none !important;
    border: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
  }
}
</style>
