<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { PopoverContent, PopoverPortal, PopoverRoot, PopoverTrigger } from 'reka-ui'
import { CalendarDays, ChevronLeft, ChevronRight } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { cn } from '@/lib/utils'
import { formatDate, parseDateValue } from '@/lib/format'
import {
  CALENDAR_MONTHS,
  isoDateFromValue,
  isDateInRange,
  parseFlexibleDateInput,
  yearsInRange,
} from '@/lib/date-picker'
import { formInputClass, formSelectTriggerClass } from '@/lib/form-standards'
import { isValidIsoDate } from '@/lib/validation'

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
    /** Show text field for DD/MM/YYYY or YYYY-MM-DD entry */
    allowTyping?: boolean
  }>(),
  {
    modelValue: '',
    placeholder: 'DD/MM/YYYY or YYYY-MM-DD',
    disabled: false,
    invalid: false,
    required: false,
    allowTyping: true,
  },
)

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const open = ref(false)
const viewDate = ref(startOfMonth(new Date()))
const textDraft = ref('')

const yearOptions = computed(() => yearsInRange(props.min, props.max))

const displayLabel = computed(() => {
  if (!props.modelValue) return props.placeholder
  return formatDate(props.modelValue)
})

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

const viewMonth = computed({
  get: () => viewDate.value.getMonth(),
  set: (month: number) => {
    const next = new Date(viewDate.value)
    next.setMonth(month)
    viewDate.value = startOfMonth(next)
  },
})

const viewYear = computed({
  get: () => viewDate.value.getFullYear(),
  set: (year: number) => {
    const next = new Date(viewDate.value)
    next.setFullYear(year)
    viewDate.value = startOfMonth(next)
  },
})

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

function toIso(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

function isDisabled(iso: string): boolean {
  return !isDateInRange(iso, props.min, props.max)
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
  textDraft.value = formatDate(iso)
  open.value = false
}

function shiftMonth(delta: number) {
  const next = new Date(viewDate.value)
  next.setMonth(next.getMonth() + delta)
  viewDate.value = startOfMonth(next)
}

function syncTextDraft(value?: string) {
  const iso = isoDateFromValue(value)
  textDraft.value = iso ? formatDate(iso) : ''
}

function commitTextInput() {
  const parsed = parseFlexibleDateInput(textDraft.value)
  if (!parsed) {
    if (!textDraft.value.trim()) {
      emit('update:modelValue', '')
    } else {
      syncTextDraft(props.modelValue)
    }
    return
  }

  if (!isValidIsoDate(parsed) || isDisabled(parsed)) {
    syncTextDraft(props.modelValue)
    return
  }

  emit('update:modelValue', parsed)
  textDraft.value = formatDate(parsed)
  const date = parseDateValue(parsed)
  if (date) viewDate.value = startOfMonth(date)
}

defineExpose({ commitPendingInput: commitTextInput })

watch(textDraft, (value) => {
  const parsed = parseFlexibleDateInput(value)
  if (!parsed || !isValidIsoDate(parsed) || isDisabled(parsed)) return
  if (parsed === isoDateFromValue(props.modelValue)) return
  emit('update:modelValue', parsed)
  textDraft.value = formatDate(parsed)
  const date = parseDateValue(parsed)
  if (date) viewDate.value = startOfMonth(date)
})

watch(
  () => props.modelValue,
  (value) => {
    syncTextDraft(value)
    const parsed = parseDateValue(value)
    if (parsed) viewDate.value = startOfMonth(parsed)
  },
  { immediate: true },
)

watch(open, (isOpen) => {
  if (isOpen) {
    const parsed = parseDateValue(props.modelValue) ?? parseDateValue(props.max) ?? new Date()
    viewDate.value = startOfMonth(parsed)
  }
})
</script>

<template>
  <div :class="cn('flex w-full gap-2', props.class)">
    <Input
      v-if="allowTyping"
      :id="id"
      v-model="textDraft"
      type="text"
      inputmode="numeric"
      autocomplete="bday"
      :disabled="disabled"
      :placeholder="placeholder"
      :aria-invalid="invalid || undefined"
      :aria-describedby="describedBy"
      :aria-required="required || undefined"
      :class="cn(formInputClass, 'h-10 flex-1 font-mono text-sm tabular-nums')"
      @keydown.enter.prevent="commitTextInput"
      @blur="commitTextInput"
    />

    <PopoverRoot v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          :id="allowTyping ? undefined : id"
          type="button"
          variant="outline"
          :disabled="disabled"
          :aria-invalid="invalid || undefined"
          :aria-describedby="describedBy"
          :aria-required="required || undefined"
          :aria-label="allowTyping ? 'Open calendar' : undefined"
          :class="cn(
            formSelectTriggerClass,
            allowTyping ? 'size-10 shrink-0 px-0' : 'h-10 flex-1 justify-start gap-2 px-3 font-normal text-sm',
            !allowTyping && !modelValue && 'text-muted-foreground',
          )"
        >
          <CalendarDays class="size-4 shrink-0 opacity-70" aria-hidden="true" />
          <span v-if="!allowTyping" class="truncate">{{ displayLabel }}</span>
        </Button>
      </PopoverTrigger>

      <PopoverPortal>
        <PopoverContent
          align="end"
          :side-offset="4"
          class="z-50 w-auto min-w-[18rem] rounded-lg border bg-popover p-3 text-popover-foreground shadow-md"
        >
          <div class="grid grid-cols-2 gap-2 pb-3">
            <label class="sr-only" for="date-picker-month">Month</label>
            <select
              id="date-picker-month"
              v-model.number="viewMonth"
              class="h-9 rounded-md border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option v-for="month in CALENDAR_MONTHS" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>

            <label class="sr-only" for="date-picker-year">Year</label>
            <select
              id="date-picker-year"
              v-model.number="viewYear"
              class="h-9 rounded-md border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option v-for="year in yearOptions" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>

          <div class="flex items-center justify-between gap-2 pb-2">
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="size-8"
              aria-label="Previous month"
              @click="shiftMonth(-1)"
            >
              <ChevronLeft class="size-4" aria-hidden="true" />
            </Button>
            <p class="text-xs font-medium text-muted-foreground">
              {{ formatDate(toIso(viewDate)) }}
            </p>
            <Button
              type="button"
              variant="ghost"
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

          <div
            class="mt-1 grid grid-cols-7 gap-1"
            role="grid"
            :aria-label="`Calendar for ${formatDate(toIso(viewDate))}`"
          >
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
                'bg-primary font-semibold text-primary-foreground hover:bg-primary hover:text-primary-foreground': cell.selected,
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
  </div>
</template>
