<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
// Corrected Lucide module import source path
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import { formatDate, parseDateValue } from '@/lib/format'
import { isoDateFromValue } from '@/lib/date-picker'
import { formSelectTriggerClass } from '@/lib/form-standards'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    min?: string
    max?: string
    disabled?: boolean
    placeholder?: string
    id?: string
    invalid?: boolean
    required?: boolean
    describedBy?: string
    class?: string
  }>(),
  {
    modelValue: '',
    placeholder: 'Select date',
    disabled: false,
    invalid: false,
    required: false,
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const viewDate = ref(startOfMonth(new Date()))

const displayLabel = computed(() => {
  if (!props.modelValue) return props.placeholder
  return formatDate(props.modelValue)
})

const monthLabel = computed(() =>
  viewDate.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' }),
)

const weekdayLabels = computed(() => {
  const base = startOfMonth(new Date())
  const day = base.getDay()
  return Array.from({ length: 7 }, (_, index) => {
    const d = new Date(base)
    d.setDate(base.getDate() - day + index)
    return d.toLocaleDateString(undefined, { weekday: 'short' })
  })
})

const calendarDays = computed(() => {
  const year = viewDate.value.getFullYear()
  const month = viewDate.value.getMonth()
  const first = new Date(year, month, 1)
  const startOffset = first.getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const cells: Array<{ iso: string; label: number; inMonth: boolean; disabled: boolean; selected: boolean; today: boolean }> = []

  for (let i = 0; i < startOffset; i += 1) {
    const date = new Date(year, month, -startOffset + i + 1)
    cells.push(buildCell(date, false))
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    cells.push(buildCell(new Date(year, month, day), true))
  }

  while (cells.length % 7 !== 0) {
    const last = cells[cells.length - 1]
    const next = parseDateValue(last.iso)
    const date = next ? new Date(next.getFullYear(), next.getMonth(), next.getDate() + 1) : new Date()
    cells.push(buildCell(date, false))
  }

  return cells
})

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

function toIso(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

function isDisabled(iso: string): boolean {
  if (props.min && iso < isoDateFromValue(props.min)) return true
  if (props.max && iso > isoDateFromValue(props.max)) return true
  return false
}

function buildCell(date: Date, inMonth: boolean) {
  const iso = toIso(date)
  const todayIso = toIso(new Date())
  return {
    iso,
    label: date.getDate(),
    inMonth,
    disabled: isDisabled(iso),
    selected: isoDateFromValue(props.modelValue) === iso,
    today: iso === todayIso,
  }
}

function selectDay(iso: string) {
  if (isDisabled(iso)) return
  emit('update:modelValue', iso)
  open.value = false
}

function shiftMonth(delta: number) {
  const next = new Date(viewDate.value)
  next.setMonth(next.getMonth() + delta)
  viewDate.value = startOfMonth(next)
}

watch(
  () => props.modelValue,
  (value) => {
    const parsed = parseDateValue(value)
    if (parsed) viewDate.value = startOfMonth(parsed)
  },
  { immediate: true },
)

watch(open, (isOpen) => {
  if (isOpen) {
    const parsed = parseDateValue(props.modelValue) ?? new Date()
    viewDate.value = startOfMonth(parsed)
  }
})
</script>

<template>
  <PopoverRoot v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        :id="id"
        type="button"
        variant="outline"
        :disabled="disabled"
        :aria-invalid="invalid || undefined"
        :aria-describedby="describedBy"
        :aria-required="required || undefined"
        :class="cn(
          formSelectTriggerClass,
          'justify-start gap-2 px-3 h-10 font-normal text-sm',
          !modelValue && 'text-muted-foreground',
          props.class,
        )"
      >
        <CalendarDays class="size-4 shrink-0 opacity-70" aria-hidden="true" />
        <span class="truncate">{{ displayLabel }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverPortal>
      <PopoverContent
        align="start"
        :side-offset="4"
        class="z-50 w-auto rounded-lg border bg-popover p-3 text-popover-foreground shadow-md"
      >
        <div class="flex items-center justify-between gap-2 pb-3">
          <Button
            type="button"
            variant="outline"
            size="icon"
            class="size-8"
            aria-label="Previous month"
            @click="shiftMonth(-1)"
          >
            <ChevronLeft class="size-4" aria-hidden="true" />
          </Button>
          <p class="text-sm font-medium text-foreground">{{ monthLabel }}</p>
          <Button
            type="button"
            variant="outline"
            size="icon"
            class="size-8"
            aria-label="Next month"
            @click="shiftMonth(1)"
          >
            <ChevronRight class="size-4" aria-hidden="true" />
          </Button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-muted-foreground">
          <span v-for="day in weekdayLabels" :key="day">{{ day }}</span>
        </div>

        <div class="mt-1 grid grid-cols-7 gap-1" role="grid" :aria-label="`Calendar for ${monthLabel}`">
          <button
            v-for="cell in calendarDays"
            :key="cell.iso"
            type="button"
            role="gridcell"
            :aria-selected="cell.selected"
            :aria-label="formatDate(cell.iso)"
            :disabled="cell.disabled"
            class="inline-flex size-9 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
            :class="{
              'text-muted-foreground/40': !cell.inMonth,
              'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground font-semibold': cell.selected,
              'bg-accent/60 font-medium text-foreground': cell.today && !cell.selected,
            }"
            @click="selectDay(cell.iso)"
          >
            {{ cell.label }}
          </button>
        </div>
      </PopoverContent>
    </PopoverPortal>
  </PopoverRoot>
</template>
