<script setup lang="ts">
import { computed, toRef } from 'vue'
import { useFormValues } from 'vee-validate'
import { useFormCascade } from '@/composables/useFormCascade'
import { useClassNameDerive } from '@/composables/useClassNameDerive'
import { Phone } from '@lucide/vue'
import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { cn } from '@/lib/utils'
import {
  formGridClass,
  formInputClass,
  formLabelClass,
  formSectionLegendClass,
  formSelectTriggerClass,
  formTextareaClass,
} from '@/lib/form-standards'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import RelationSelect from './RelationSelect.vue'
import MultiRelationSelect from './MultiRelationSelect.vue'
import GuardianLinkSection from './GuardianLinkSection.vue'
import DatePicker from './DatePicker.vue'

const props = withDefaults(
  defineProps<{
    fields: FormFieldSchema[]
    /** 1-col for compact sheets; 2-col default; 3-col for wide panels */
    columns?: 1 | 2 | 3
    /** When set, only the section at this index is rendered (wizard mode). */
    visibleStepIndex?: number | null
    /** Hide section legends (wizard shows step title externally). */
    hideSectionLegends?: boolean
  }>(),
  { columns: 2, visibleStepIndex: null, hideSectionLegends: false },
)

useFormCascade(toRef(() => props.fields))
useClassNameDerive(toRef(() => props.fields))

const formValues = useFormValues()

function matchesVisibleWhen(field: FormFieldSchema, values: Record<string, unknown> | undefined): boolean {
  const rule = field.visibleWhen
  if (!rule) return true
  const raw = values?.[rule.field]
  const current = raw == null ? '' : String(raw)
  const expected = Array.isArray(rule.equals) ? rule.equals : [rule.equals]
  return expected.includes(current)
}

function fieldRenderKey(field: FormFieldSchema): string {
  if (!field.visibleWhen) return field.name
  const equals = Array.isArray(field.visibleWhen.equals)
    ? field.visibleWhen.equals.join('|')
    : field.visibleWhen.equals
  return `${field.name}:${field.visibleWhen.field}=${equals}`
}

interface FieldGroup {
  title: string
  fields: FormFieldSchema[]
}

const groups = computed<FieldGroup[]>(() => {
  const values = formValues.value as Record<string, unknown> | undefined
  const result: FieldGroup[] = []
  let current: FieldGroup = { title: '', fields: [] }

  for (const field of props.fields) {
    if (!matchesVisibleWhen(field, values)) continue
    const section = field.section ?? ''
    if (section !== current.title) {
      if (current.fields.length) result.push(current)
      current = { title: section, fields: [field] }
    } else {
      current.fields.push(field)
    }
  }

  if (current.fields.length) result.push(current)
  return result
})

const visibleGroups = computed(() => {
  if (props.visibleStepIndex == null) return groups.value
  const group = groups.value[props.visibleStepIndex]
  return group ? [group] : []
})

const gridClass = computed(() => formGridClass(props.columns))

// Determines column spacing rules dynamically across layout tiers
function colClass(field: FormFieldSchema) {
  if (props.columns === 1) return ''
  if (field.type === 'guardian-section' || field.type === 'multirelation') {
    return props.columns === 3 ? 'sm:col-span-2 lg:col-span-3' : 'sm:col-span-2'
  }
  if (field.colSpan === 2) {
    return props.columns === 3 ? 'sm:col-span-2 lg:col-span-3' : 'sm:col-span-2'
  }
  return 'sm:col-span-1'
}
</script>

<template>
  <div class="w-full space-y-8">
    <fieldset
      v-for="(group, index) in visibleGroups"
      :key="`${group.title}-${index}`"
      class="w-full space-y-4 border-0 p-0"
    >
      <legend
        v-if="group.title && !hideSectionLegends"
        :class="formSectionLegendClass"
      >
        {{ group.title }}
      </legend>

      <div :class="gridClass">
        <div
          v-for="field in group.fields"
          :key="fieldRenderKey(field)"
          :class="cn('min-w-0', colClass(field))"
        >
          <GuardianLinkSection v-if="field.type === 'guardian-section'" />

          <FormField v-else v-slot="{ componentField }" :name="field.name">
            <FormItem class="gap-2">
              <FormLabel
                v-if="field.type !== 'checkbox'"
                :class="cn(formLabelClass, 'flex items-center gap-1')"
              >
                {{ field.label }}
                <span v-if="field.required" class="text-destructive" aria-hidden="true">*</span>
                <span v-if="field.required" class="sr-only">(required)</span>
              </FormLabel>

              <FormDescription
                v-if="field.description"
                :id="`${field.name}-description`"
                class="text-sm text-muted-foreground"
              >
                {{ field.description }}
              </FormDescription>

              <FormControl>
                <Textarea
                  v-if="field.type === 'textarea'"
                  v-bind="componentField"
                  :placeholder="field.placeholder"
                  :aria-required="field.required || undefined"
                  :aria-describedby="field.description ? `${field.name}-description` : undefined"
                  :class="formTextareaClass"
                />

                <RelationSelect
                  v-else-if="field.type === 'relation' && field.relation"
                  :field="field"
                  :model-value="componentField.modelValue"
                  @update:model-value="componentField['onUpdate:modelValue']"
                />

                <MultiRelationSelect
                  v-else-if="field.type === 'multirelation' && field.relation"
                  :field="field"
                  :model-value="componentField.modelValue"
                  @update:model-value="componentField['onUpdate:modelValue']"
                />

                <p
                  v-else-if="field.type === 'relation' || field.type === 'multirelation'"
                  class="text-sm italic text-muted-foreground"
                  role="status"
                >
                  This field is not configured. Contact your administrator.
                </p>

                <Select
                  v-else-if="field.type === 'select'"
                  :model-value="componentField.modelValue !== undefined && componentField.modelValue !== null ? String(componentField.modelValue) : undefined"
                  :disabled="field.disabled"
                  @update:model-value="componentField['onUpdate:modelValue']"
                >
                  <SelectTrigger
                    :class="formSelectTriggerClass"
                    :disabled="field.disabled"
                    :aria-required="field.required || undefined"
                    :aria-describedby="field.description ? `${field.name}-description` : undefined"
                  >
                    <SelectValue :placeholder="field.placeholder ?? 'Select an option…'" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in field.options ?? []"
                      :key="String(option.value)"
                      :value="String(option.value)"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <div
                  v-else-if="field.type === 'checkbox'"
                  class="flex min-h-10 items-center gap-2.5"
                >
                  <Checkbox
                    :id="field.name"
                    :checked="Boolean(componentField.modelValue)"
                    :aria-describedby="field.description ? `${field.name}-description` : undefined"
                    @update:checked="componentField['onUpdate:modelValue']"
                  />
                  <label
                    :for="field.name"
                    :class="cn(formLabelClass, 'cursor-pointer select-none font-normal')"
                  >
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive" aria-hidden="true">*</span>
                    <span v-if="field.required" class="sr-only">(required)</span>
                  </label>
                </div>

                <DatePicker
                  v-else-if="field.type === 'date'"
                  :id="field.name"
                  :model-value="String(componentField.modelValue ?? '')"
                  :min="field.min"
                  :max="field.max"
                  :placeholder="field.placeholder ?? 'Select date'"
                  :required="field.required"
                  :described-by="field.description ? `${field.name}-description` : undefined"
                  @update:model-value="componentField['onUpdate:modelValue']"
                />

                <div v-else-if="field.type === 'phone'" class="relative w-full">
                  <Phone
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground/80"
                    aria-hidden="true"
                  />
                  <Input
                    v-bind="componentField"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    :placeholder="field.placeholder"
                    :aria-required="field.required || undefined"
                    :aria-describedby="field.description ? `${field.name}-description` : undefined"
                    :class="cn(formInputClass, 'pl-9')"
                  />
                </div>

                <Input
                  v-else
                  v-bind="componentField"
                  :type="field.type"
                  :placeholder="field.placeholder"
                  :aria-required="field.required || undefined"
                  :aria-describedby="field.description ? `${field.name}-description` : undefined"
                  :class="cn(formInputClass, field.type === 'number' && 'tabular-nums')"
                />
              </FormControl>

              <FormMessage />
            </FormItem>
          </FormField>
        </div>
      </div>
    </fieldset>
  </div>
</template>
