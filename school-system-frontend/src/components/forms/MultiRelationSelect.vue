<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Skeleton } from '@/components/ui/skeleton'
import { loadRelationOptions, type RelationOption } from '@/lib/relation-options'
import type { FormFieldSchema } from './useFormBuilder'

const props = defineProps<{
  field: FormFieldSchema
  modelValue?: string | number | string[] | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const options = ref<RelationOption[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const selectedIds = computed(() => {
  const raw = props.modelValue
  if (Array.isArray(raw)) {
    return raw.map(String).filter(Boolean)
  }
  if (raw == null || raw === '') return []
  return String(raw)
    .split(/[\s,]+/)
    .map((id) => id.trim())
    .filter(Boolean)
})

function toggle(id: string, checked: boolean | 'indeterminate') {
  const next = new Set(selectedIds.value)
  if (checked === true) next.add(id)
  else next.delete(id)
  emit('update:modelValue', Array.from(next).join(', '))
}

async function loadOptions() {
  if (!props.field.relation) return
  loading.value = true
  error.value = null
  try {
    options.value = await loadRelationOptions(
      props.field.relation.endpoint,
      props.field.relation.params ?? props.field.relation.queryParams ?? {},
    )
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load options'
    options.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadOptions()
})

watch(
  () => props.field.relation?.endpoint,
  () => {
    void loadOptions()
  },
)
</script>

<template>
  <div class="space-y-2" role="group" :aria-label="field.label">
    <Skeleton v-if="loading" class="h-24 w-full" />
    <p v-else-if="error" class="text-sm text-destructive" role="alert">{{ error }}</p>
    <p v-else-if="!options.length" class="text-sm text-muted-foreground">
      No {{ field.relation?.moduleLabel ?? 'options' }} available yet.
    </p>
    <ul v-else class="max-h-48 space-y-2 overflow-y-auto rounded-md border border-border p-3">
      <li
        v-for="option in options"
        :key="option.value"
        class="flex items-center gap-2"
      >
        <Checkbox
          :id="`${field.name}-${option.value}`"
          :checked="selectedIds.includes(option.value)"
          @update:checked="(v: boolean | 'indeterminate') => toggle(option.value, v)"
        />
        <label
          :for="`${field.name}-${option.value}`"
          class="cursor-pointer text-sm font-normal leading-none"
        >
          {{ option.label }}
        </label>
      </li>
    </ul>
  </div>
</template>
