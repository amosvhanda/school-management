<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Skeleton } from '@/components/ui/skeleton'
import SearchableSelect from '@/components/forms/SearchableSelect.vue'
import { loadRelationOptions, type RelationOption } from '@/lib/relation-options'
import type { ListRelationFilterConfig } from '@/modules/shared/list-filters'

const props = defineProps<{
  modelValue?: string
  label: string
  placeholder?: string
  relation: ListRelationFilterConfig
  parentValue?: string
  disabled?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const options = ref<RelationOption[]>([])
const loading = ref(true)

const isBlocked = computed(() => {
  const rule = props.relation.dependsOn
  return Boolean(rule && !props.parentValue)
})

const requestParams = computed(() => {
  const rule = props.relation.dependsOn
  if (!rule || !props.parentValue) return undefined
  const paramKey = rule.paramKey ?? rule.field
  return { [paramKey]: props.parentValue }
})

const selectOptions = computed(() =>
  options.value.map((option) => ({ value: option.value, label: option.label })),
)

const allLabel = computed(
  () => props.placeholder ?? `All ${props.relation.moduleLabel ?? 'records'}`,
)

async function loadOptions() {
  if (isBlocked.value) {
    options.value = []
    loading.value = false
    return
  }
  loading.value = true
  try {
    options.value = await loadRelationOptions(props.relation.endpoint, requestParams.value)
  } finally {
    loading.value = false
  }
}

onMounted(loadOptions)

watch([() => props.parentValue, () => props.relation.endpoint], () => {
  if (isBlocked.value) {
    emit('update:modelValue', '')
    options.value = []
    return
  }
  void loadOptions()
})

function onChange(value: string) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="min-w-[10rem] flex-1 sm:max-w-[14rem]">
    <label class="sr-only">{{ label }}</label>
    <Skeleton v-if="loading && !options.length" class="h-9 w-full rounded-md" />
    <SearchableSelect
      v-else
      :model-value="modelValue ?? ''"
      :options="selectOptions"
      :loading="loading"
      :disabled="disabled || isBlocked"
      :placeholder="allLabel"
      :all-label="allLabel"
      :empty-message="`No ${relation.moduleLabel ?? 'records'} match your search.`"
      show-refresh
      class="h-9 text-xs"
      @update:model-value="onChange"
      @refresh="loadOptions"
    />
  </div>
</template>
