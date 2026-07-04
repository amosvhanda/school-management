<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import type { GenericObject, SubmissionHandler } from 'vee-validate'
import { z } from 'zod'
import RolePermissionsPicker from '@/modules/admin/components/RolePermissionsPicker.vue'
import type { PermissionRecord, RoleRecord } from '@/modules/admin/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { getValidationErrors } from '@/lib/api-response'
import { formSurfaceClass } from '@/lib/form-standards'
import { cn } from '@/lib/utils'

const open = defineModel<boolean>('open', { required: true })

type RoleFormValues = {
  name: string
  description?: string
}

const props = defineProps<{
  role: RoleRecord | null
  permissions: PermissionRecord[]
  saving?: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [payload: { name: string; description: string; permission_ids: number[] }]
}>()

const schema = z.object({
  name: z.string().trim().min(1, 'Role name is required'),
  description: z.string().trim().optional().or(z.literal('')),
})

const form = useForm<RoleFormValues>({
  validationSchema: toTypedSchema(schema),
  initialValues: { name: '', description: '' },
})

const permissionIds = ref<number[]>([])

const isEditing = computed(() => Boolean(props.role?.id))
const title = computed(() => (isEditing.value ? 'Edit role' : 'Create role'))
const isSystemRole = computed(() => Boolean(props.role?.is_system))

watch(
  () => [open.value, props.role] as const,
  ([isOpen, role]) => {
    if (!isOpen) return
    form.resetForm({
      values: {
        name: role?.name ?? '',
        description: role?.description ?? '',
      },
    })
    permissionIds.value = role?.permission_ids
      ?? role?.permissions?.map((p) => p.id)
      ?? []
  },
  { immediate: true },
)

function applyServerErrors(error: unknown) {
  const errors = getValidationErrors(error)
  Object.entries(errors).forEach(([field, messages]) => {
    if (field === 'permission_ids') return
    form.setFieldError(field as 'name' | 'description', messages[0])
  })
}

defineExpose({ applyServerErrors })

const onSubmit = form.handleSubmit((formValues: RoleFormValues) => {
  emit('submit', {
    name: formValues.name,
    description: formValues.description ?? '',
    permission_ids: permissionIds.value,
  })
}) as unknown as SubmissionHandler<GenericObject>
</script>

<template>
  <Dialog v-model:open="open">
    <!-- DialogContent automatically places the panel right in the middle of the page -->
    <DialogContent
      :class="cn(
        'flex max-h-[85vh] w-full max-w-xl flex-col gap-0 overflow-hidden p-0',
        formSurfaceClass,
      )"
    >
      <DialogHeader class="shrink-0 space-y-1 border-b border-muted/60 px-6 pb-4 pt-6">
        <DialogTitle class="text-base font-semibold tracking-tight">{{ title }}</DialogTitle>
        <DialogDescription class="text-xs leading-relaxed">
          <template v-if="isSystemRole">
            Built-in role — you can update permissions but the slug stays fixed.
          </template>
          <template v-else>
            Define the role name, description, and which permissions it grants.
          </template>
        </DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto px-6 py-6">
        <PageLoader v-if="loading" label="Loading role…" />

        <Form v-else id="role-form" class="space-y-6" @submit="onSubmit">
          <div class="space-y-4">

            <!-- Role Name Field -->
            <FormField v-slot="{ componentField }" name="name">
              <FormItem>
                <FormLabel>Role name</FormLabel>
                <FormControl>
                  <Input
                    type="text"
                    placeholder="Finance Officer"
                    v-bind="componentField"
                    :disabled="saving"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

            <!-- Description Field -->
            <FormField v-slot="{ componentField }" name="description">
              <FormItem>
                <FormLabel>Description</FormLabel>
                <FormControl>
                  <Textarea
                    placeholder="What this role can access in the system"
                    rows="3"
                    v-bind="componentField"
                    :disabled="saving"
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            </FormField>

          </div>

          <RolePermissionsPicker
            v-model="permissionIds"
            :permissions="permissions"
          />
        </Form>
      </div>

      <DialogFooter class="shrink-0 border-t border-muted/60 px-6 py-4 sm:flex-row sm:justify-end gap-2">
        <Button type="button" variant="outline" :disabled="saving" @click="open = false">
          Cancel
        </Button>
        <Button type="submit" form="role-form" :disabled="saving || loading">
          {{ saving ? 'Saving…' : isEditing ? 'Save changes' : 'Create role' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
