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
  document: Record<string, unknown> | null
  loading?: boolean
}>()

defineEmits<{ 'update:open': [value: boolean] }>()

const student = computed(() => props.document?.student as Record<string, unknown> | undefined)
const school = computed(() => props.document?.school as Record<string, unknown> | undefined)

const initials = computed(() => {
  const name = String(student.value?.full_name ?? '')
  const parts = name.trim().split(/\s+/).filter(Boolean)
  if (!parts.length) return 'ST'
  return parts.slice(0, 2).map((part) => part[0]?.toUpperCase() ?? '').join('')
})

const accentStyle = computed(() => ({
  borderColor: String(school.value?.primary_color ?? '#FF7A00'),
  color: String(school.value?.primary_color ?? '#FF7A00'),
}))

function printCard() {
  window.print()
}
</script>

<template>
  <Sheet :open="open" @update:open="$emit('update:open', $event)">
    <SheetContent class="w-full overflow-y-auto sm:max-w-md print:max-w-none" side="right">
      <SheetHeader class="print:hidden">
        <SheetTitle>Student ID card</SheetTitle>
        <SheetDescription>Printable student identification card</SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="py-8 text-sm text-muted-foreground">Loading ID card…</div>

      <article
        v-else-if="student"
        class="mt-6 space-y-4 print:mt-0"
        aria-label="Student ID card"
      >
        <div
          class="mx-auto w-full max-w-sm overflow-hidden rounded-2xl border-2 bg-card shadow-sm print:shadow-none"
          :style="{ borderColor: String(school?.primary_color ?? '#FF7A00') }"
        >
          <div
            class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-white"
            :style="{ backgroundColor: String(school?.primary_color ?? '#FF7A00') }"
          >
            Student ID
          </div>

          <div class="space-y-4 p-5">
            <div class="text-center space-y-1">
              <h2 class="text-lg font-semibold leading-tight">{{ school?.name ?? 'School' }}</h2>
              <p v-if="school?.motto" class="text-xs text-muted-foreground">{{ school.motto }}</p>
            </div>

            <div class="flex items-center gap-4">
              <div
                class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted text-xl font-semibold"
                :style="accentStyle"
                aria-hidden="true"
              >
                <img
                  v-if="student.photo_url"
                  :src="String(student.photo_url)"
                  alt=""
                  class="size-full object-cover"
                />
                <span v-else>{{ initials }}</span>
              </div>

              <dl class="min-w-0 flex-1 space-y-1 text-sm">
                <div>
                  <dt class="sr-only">Student name</dt>
                  <dd class="font-semibold leading-tight">{{ student.full_name ?? '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-muted-foreground">Student no.</dt>
                  <dd class="font-mono text-xs">{{ student.student_number ?? '—' }}</dd>
                </div>
                <div>
                  <dt class="text-xs text-muted-foreground">Class</dt>
                  <dd>{{ student.class ?? '—' }}</dd>
                </div>
                <div v-if="student.grade_level">
                  <dt class="text-xs text-muted-foreground">Grade</dt>
                  <dd>{{ student.grade_level }}</dd>
                </div>
              </dl>
            </div>

            <dl class="grid grid-cols-2 gap-3 border-t pt-3 text-xs">
              <div v-if="student.date_of_birth">
                <dt class="text-muted-foreground">Date of birth</dt>
                <dd>{{ formatDate(student.date_of_birth) }}</dd>
              </div>
              <div v-if="student.gender">
                <dt class="text-muted-foreground">Gender</dt>
                <dd class="capitalize">{{ student.gender }}</dd>
              </div>
              <div v-if="student.house" class="col-span-2">
                <dt class="text-muted-foreground">House</dt>
                <dd>{{ student.house }}</dd>
              </div>
            </dl>

            <p class="border-t pt-3 text-center font-mono text-[10px] text-muted-foreground">
              {{ document?.document_number }}
            </p>
          </div>
        </div>

        <div class="flex justify-end print:hidden">
          <Button variant="outline" @click="printCard">
            <Printer class="mr-2 h-4 w-4" aria-hidden="true" />
            Print ID card
          </Button>
        </div>
      </article>
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
