<script setup lang="ts">
import { ref, watch } from 'vue'
import RolePermissionsPicker from '@/modules/admin/components/RolePermissionsPicker.vue'
import type { PermissionRecord } from '@/modules/admin/types'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import PageLoader from '@/components/feedback/PageLoader.vue'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
  userName: string
  permissions: PermissionRecord[]
  permissionIds: number[]
  saving?: boolean
  loading?: boolean
}>()

const emit = defineEmits<{
  submit: [permissionIds: number[]]
}>()

const selectedIds = ref<number[]>([])

watch(
  () => [open.value, props.permissionIds] as const,
  ([isOpen]) => {
    if (!isOpen) return
    selectedIds.value = [...(props.permissionIds ?? [])]
  },
  { immediate: true },
)
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent
      class="flex max-h-[90vh] w-full flex-col gap-0 overflow-hidden p-0 sm:max-w-xl"
    >
      <DialogHeader class="shrink-0 space-y-1 border-b border-border/60 px-6 pt-6 pb-4">
        <DialogTitle class="text-base font-semibold tracking-tight">
          Extra module access
        </DialogTitle>
        <DialogDescription class="text-xs leading-relaxed">
          Grant {{ userName }} additional modules (library, transport, inventory, reception)
          without changing their login role. These stack on top of role permissions.
        </DialogDescription>
      </DialogHeader>

      <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
        <PageLoader v-if="loading" class="py-10" label="Loading permissions…" />
        <RolePermissionsPicker
          v-else
          v-model="selectedIds"
          :permissions="permissions"
        />
      </div>

      <DialogFooter class="shrink-0 gap-2 border-t border-muted/60 px-6 py-4 sm:justify-end">
        <Button type="button" variant="outline" :disabled="saving" @click="open = false">
          Cancel
        </Button>
        <Button type="button" :disabled="saving || loading" @click="emit('submit', selectedIds)">
          {{ saving ? 'Saving…' : 'Save access' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
