<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { ErrorMessage } from "vee-validate"
import { toValue } from "vue"
import { cn } from "@/lib/utils"
import { formMessageAreaClass, formFieldsAnimateOptions } from "@/lib/form-standards"
import { useFormField } from "./useFormField"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { name, formMessageId } = useFormField()
</script>

<template>
  <div v-auto-animate="formFieldsAnimateOptions" :class="formMessageAreaClass">
    <ErrorMessage
      :id="formMessageId"
      data-slot="form-message"
      as="p"
      :name="toValue(name)"
      :class="cn('text-xs leading-snug text-destructive', props.class)"
    />
  </div>
</template>
