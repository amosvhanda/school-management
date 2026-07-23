<script setup lang="ts">
import { useFieldValue } from 'vee-validate'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import RelationSelect from './RelationSelect.vue'
import { formFieldsAnimateOptions } from '@/lib/form-standards'
import { guardianRelationshipOptions } from '@/modules/guardians/guardian-form'
import { moduleEndpoints } from '@/services'
import type { FormFieldSchema } from './useFormBuilder.ts'

type GuardianMode = 'existing' | 'new' | 'none'

const guardianMode = useFieldValue<GuardianMode>('guardianMode')

const modeOptions = [
  { label: 'Link existing', value: 'existing' },
  { label: 'Register new', value: 'new' },
  { label: 'None', value: 'none' },
] as const

const guardianSelectField: FormFieldSchema = {
  name: 'guardian_id',
  label: 'Guardian',
  type: 'relation',
  required: true,
  placeholder: 'Select guardian',
  relation: {
    endpoint: moduleEndpoints.guardians,
    createRoute: '/guardians?create=1',
    moduleLabel: 'guardian',
  },
}
</script>

<template>
  <div class="space-y-4">
    <FormField v-slot="{ componentField }" name="guardianMode">
      <FormItem>
        <FormLabel class="text-sm font-semibold text-foreground">Link type</FormLabel>
        <FormControl>
          <Select
            :model-value="componentField.modelValue"
            @update:model-value="componentField['onUpdate:modelValue']"
          >
            <SelectTrigger class="h-10">
              <SelectValue placeholder="Select link type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="option in modeOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormControl>
        <FormMessage />
      </FormItem>
    </FormField>

    <div
      v-if="guardianMode === 'existing'"
      v-auto-animate="formFieldsAnimateOptions"
      class="grid gap-4 sm:grid-cols-2"
    >
      <FormField v-slot="{ componentField }" name="guardian_id" class="sm:col-span-2">
        <FormItem>
          <FormLabel class="flex items-center gap-1 text-sm font-semibold text-foreground">
            Guardian
            <span class="text-destructive" aria-hidden="true">*</span>
          </FormLabel>
          <FormControl>
            <RelationSelect
              :field="guardianSelectField"
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="guardianRelationship" class="sm:col-span-2">
        <FormItem>
          <FormLabel class="text-sm font-semibold text-foreground">Relationship</FormLabel>
          <FormControl>
            <Select
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
            >
              <SelectTrigger class="h-10">
                <SelectValue placeholder="Select relationship" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in guardianRelationshipOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>

    <div
      v-if="guardianMode === 'new'"
      v-auto-animate="formFieldsAnimateOptions"
      class="grid gap-4 sm:grid-cols-2"
    >
      <FormField v-slot="{ componentField }" name="guardianFirstName">
        <FormItem>
          <FormLabel class="flex items-center gap-1 text-sm font-semibold text-foreground">
            First name
            <span class="text-destructive" aria-hidden="true">*</span>
          </FormLabel>
          <FormControl>
            <Input v-bind="componentField" placeholder="Rudo" class="h-10" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="guardianSurname">
        <FormItem>
          <FormLabel class="text-sm font-semibold text-foreground">Surname</FormLabel>
          <FormControl>
            <Input v-bind="componentField" placeholder="Moyo" class="h-10" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="guardianPhone">
        <FormItem>
          <FormLabel class="flex items-center gap-1 text-sm font-semibold text-foreground">
            Mobile
            <span class="text-destructive" aria-hidden="true">*</span>
          </FormLabel>
          <FormControl>
            <Input v-bind="componentField" type="tel" inputmode="tel" placeholder="077 123 4567" class="h-10" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="guardianEmail">
        <FormItem>
          <FormLabel class="text-sm font-semibold text-foreground">Email</FormLabel>
          <FormControl>
            <Input v-bind="componentField" type="email" placeholder="parent@example.com" class="h-10" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="guardianRelationship" class="sm:col-span-2">
        <FormItem>
          <FormLabel class="text-sm font-semibold text-foreground">Relationship</FormLabel>
          <FormControl>
            <Select
              :model-value="componentField.modelValue"
              @update:model-value="componentField['onUpdate:modelValue']"
            >
              <SelectTrigger class="h-10">
                <SelectValue placeholder="Select relationship" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="option in guardianRelationshipOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>
    </div>
  </div>
</template>
