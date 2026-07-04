<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useFormValues } from 'vee-validate'
import { RouterLink } from 'vue-router'
// Corrected Lucide module source path
import { ExternalLink, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import SearchableSelect from '@/components/forms/SearchableSelect.vue'
import {
  findRelationLabel,
  loadRelationOptions,
  type RelationOption,
} from '@/lib/relation-options'
import type { FormFieldSchema } from './useFormBuilder'

const props = defineProps<{
  field: FormFieldSchema
  modelValue?: string | number | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const formValues = useFormValues()
const options = ref<RelationOption[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const createRoute = computed(() => props.field.relation?.createRoute)
const moduleLabel = computed(() => props.field.relation?.moduleLabel ?? 'record')
const dependsOn = computed(() => props.field.relation?.dependsOn)

const parentValue = computed(() => {
  const rule = dependsOn.value
  if (!rule) return undefined
  const values = formValues.value as Record<string, unknown> | undefined
  const raw = values?.[rule.field]
  return raw == null || raw === '' ? undefined : String(raw)
})

const isBlocked = computed(() => Boolean(dependsOn.value && !parentValue.value))

const requestParams = computed(() => {
  const base = props.field.relation?.params ?? props.field.relation?.queryParams ?? {}
  const rule = dependsOn.value
  if (!rule || !parentValue.value) return base
  const paramKey = rule.paramKey ?? rule.field
  return { ...base, [paramKey]: parentValue.value }
})

const selectOptions = computed(() =>
  options.value.map((option) => ({ value: option.value, label: option.label })),
)

const selectedLabel = computed(() => {
  if (!props.modelValue) return null
  return findRelationLabel(
    props.field.relation?.endpoint ?? '',
    props.modelValue,
    requestParams.value,
  ) ?? options.value.find((o) => o.value === String(props.modelValue))?.label
})

async function loadOptions() {
  if (!props.field.relation) return
  if (isBlocked.value) {
    options.value = []
    loading.value = false
    return
  }

  loading.value = true
  error.value = null
  try {
    options.value = await loadRelationOptions(
      props.field.relation.endpoint,
      requestParams.value,
      true,
    )
  } catch {
    error.value = 'Could not load options'
  } finally {
    loading.value = false
  }
}

onMounted(loadOptions)

watch(parentValue, () => {
  const rule = dependsOn.value
  if (rule?.clearOnChange === false) {
    void loadOptions()
    return
  }

  if (props.modelValue != null && props.modelValue !== '') {
    emit('update:modelValue', '')
  }

  void loadOptions()
})

function onChange(value: string) {
  emit('update:modelValue', value)
}

function clearSelection() {
  emit('update:modelValue', '')
}
</script>

<template>
  <div class="space-y-2">
    <div
      v-if="isBlocked"
      class="rounded-lg border border-dashed border-border bg-muted/20 px-3 py-4 text-xs text-muted-foreground leading-normal"
      role="status"
    >
      Select {{ dependsOn?.field.replace(/_/g, ' ') }} first to load {{ field.label.toLowerCase() }} options.
    </div>

    <div
      v-else-if="loading && !options.length"
      class="space-y-2"
      role="status"
      aria-live="polite"
      aria-busy="true"
    >
      <Skeleton class="h-10 w-full rounded-lg" />
    </div>

    <template v-else>
      <div v-if="modelValue && selectedLabel" class="flex items-center gap-2 rounded-lg border border-muted/60 bg-muted/20 p-2.5 shadow-sm">
        <div class="min-w-0 flex-1">
          <p class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">Selected {{ field.label.toLowerCase() }}</p>
          <p class="truncate text-sm font-semibold text-foreground mt-0.5">{{ selectedLabel }}</p>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="size-8 shrink-0 hover:bg-destructive/10 hover:text-destructive"
          aria-label="Clear selection"
          @click="clearSelection"
        >
          <X class="size-4" />
        </Button>
      </div>

      <SearchableSelect
        :model-value="modelValue != null && modelValue !== '' ? String(modelValue) : ''"
        :options="selectOptions"
        :loading="loading"
        :placeholder="props.field.placeholder ?? `Search and select ${field.label.toLowerCase()}…`"
        :empty-message="`No ${moduleLabel} matches your search.`"
        :invalid="Boolean(error)"
        :required="props.field.required"
        :described-by="error ? `${field.name}-error` : undefined"
        show-refresh
        @update:model-value="onChange"
        @refresh="loadOptions"
      />

      <div v-if="createRoute && !modelValue" class="flex items-center justify-between gap-2 text-xs text-muted-foreground px-0.5">
        <span>Can't find who you need?</span>
        <Button variant="link" size="sm" class="h-auto p-0 text-xs font-normal text-primary hover:underline" as-child>
          <RouterLink :to="createRoute" class="inline-flex items-center gap-1">
            Register new {{ moduleLabel }}
            <ExternalLink class="size-3" aria-hidden="true" />
          </RouterLink>
        </Button>
      </div>
    </template>

    <p v-if="error" :id="`${field.name}-error`" class="text-xs font-medium text-destructive mt-1" role="alert">{{ error }}</p>
  </div>
</template>
