<script setup lang="ts">
import { computed, toRef } from 'vue'
import { useFormCascade } from '@/composables/useFormCascade'
import { Phone } from 'lucide-vue-next'
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

interface FieldGroup {
  title: string
  fields: FormFieldSchema[]
}

const groups = computed<FieldGroup[]>(() => {
  const result: FieldGroup[] = []
  let current: FieldGroup = { title: '', fields: [] }

  for (const field of props.fields) {
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
  if (field.type === 'guardian-section') {
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
          :key="field.name"
          :class="cn('min-w-0', colClass(field))"
        >
          <!-- Special composite block wrapper -->
          <GuardianLinkSection v-if="field.type === 'guardian-section'" />

          <FormField v-else v-slot="{ componentField }" :name="field.name">
            <FormItem>
              <!-- Labels remain hidden for background panel attachments -->
              <FormLabel
                v-if="field.type !== 'checkbox'"
                :class="cn(formLabelClass, 'flex items-center gap-1')"
              >
                {{ field.label }}
                <span v-if="field.required" class="text-destructive" aria-hidden="true">*</span>
                <span v-if="field.required" class="sr-only">(required)</span>
              </FormLabel>

              <FormDescription v-if="field.description" :id="`${field.name}-description`">
                {{ field.description }}
              </FormDescription>

              <FormControl>
                <!-- Textarea Inputs -->
                <Textarea
                  v-if="field.type === 'textarea'"
                  v-bind="componentField"
                  :placeholder="field.placeholder"
                  :aria-required="field.required || undefined"
                  :aria-describedby="field.description ? `${field.name}-description` : undefined"
                  :class="formTextareaClass"
                />

                <!-- Relational Select Fields (These typically manage internal search/async filtering) -->
                <RelationSelect
                  v-else-if="field.type === 'relation' && field.relation"
                  :field="field"
                  :model-value="componentField.modelValue"
                  @update:model-value="componentField['onUpdate:modelValue']"
                />

                <p
                  v-else-if="field.type === 'relation'"
                  class="text-sm text-muted-foreground italic"
                  role="status"
                >
                  This field is not configured. Contact your administrator.
                </p>

                <!-- Standard Configuration Dropdowns -->
                <Select
                  v-else-if="field.type === 'select'"
                  :model-value="componentField.modelValue !== undefined && componentField.modelValue !== null ? String(componentField.modelValue) : undefined"
                  @update:model-value="componentField.onInput"
                >
                  <SelectTrigger
                    :class="formSelectTriggerClass"
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

                <!-- Checkbox Flags -->
                <div v-if="field.type === 'checkbox'" class="flex items-center gap-2 pt-1">
                  <Checkbox
                    :id="field.name"
                    :checked="Boolean(componentField.modelValue)"
                    :aria-describedby="field.description ? `${field.name}-description` : undefined"
                    @update:checked="componentField['onUpdate:modelValue']"
                  />
                  <label
                    :for="field.name"
                    :class="cn(formLabelClass, 'cursor-pointer select-none font-normal text-sm')"
                  >
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive" aria-hidden="true">*</span>
                    <span v-if="field.required" class="sr-only">(required)</span>
                  </label>
                </div>

                <!-- Calendar Dates -->
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

                <!-- Phone Line Masks -->
                <div v-else-if="field.type === 'phone'" class="relative w-full">
                  <Phone
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground/80"
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
                    :class="cn(formInputClass, 'pl-9 h-10')"
                  />
                </div>

                <!-- Standard Text/Numeric Primitive Catch-all -->
                <Input
                  v-else
                  v-bind="componentField"
                  :type="field.type"
                  :placeholder="field.placeholder"
                  :aria-required="field.required || undefined"
                  :aria-describedby="field.description ? `${field.name}-description` : undefined"
                  :class="cn(formInputClass, 'h-10', field.type === 'number' && 'tabular-nums')"
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
