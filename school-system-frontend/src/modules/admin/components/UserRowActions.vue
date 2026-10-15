<script setup lang="ts">
import { MoreHorizontal } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  TABLE_ACTION_ICONS,
  tableActionIconClass,
  tableActionMenuIconClass,
} from '@/components/data-table/table-action-icons'
import { useToast } from '@/components/ui/toast/use-toast'
import { getErrorMessage } from '@/lib/api-response'
import { usersApi } from '@/services/api.service'

const props = defineProps<{
  user: { id: number; status: string }
}>()

const emit = defineEmits<{
  edit: []
  permissions: []
  refresh: []
}>()

const { toast } = useToast()

async function toggleStatus() {
  const isDeactivating = props.user.status === 'active'
  try {
    if (isDeactivating) {
      await usersApi.deactivate(props.user.id)
    } else {
      await usersApi.activate(props.user.id)
    }

    toast({
      title: `User ${isDeactivating ? 'deactivated' : 'activated'}`,
      description: 'The user account state has been successfully updated.',
    })
    emit('refresh')
  } catch (err) {
    toast({
      title: 'Action failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}

async function resetPassword() {
  try {
    const result = (await usersApi.resetPassword(props.user.id, {})) as {
      temporary_password?: string
      message?: string
    }
    const temporaryPassword = result?.temporary_password
    toast({
      title: 'Password reset successful',
      description: temporaryPassword
        ? `Temporary password: ${temporaryPassword}. Share it securely — it will not be shown again.`
        : 'A temporary password was generated for this user.',
    })
  } catch (err) {
    toast({
      title: 'Reset failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}
</script>

<template>
  <div class="flex items-center justify-end gap-0.5">
    <Button
      type="button"
      variant="ghost"
      size="icon"
      class="size-8"
      aria-label="Edit user"
      @click="emit('edit')"
    >
      <component :is="TABLE_ACTION_ICONS.edit" :class="tableActionIconClass" aria-hidden="true" />
    </Button>

    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button variant="ghost" size="icon" class="size-8" aria-label="More user actions">
          <MoreHorizontal :class="tableActionIconClass" aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent align="end" class="min-w-[11rem]">
        <DropdownMenuItem @click="emit('permissions')">
          <component
            :is="TABLE_ACTION_ICONS.permissions"
            :class="tableActionMenuIconClass"
            aria-hidden="true"
          />
          Extra module access
        </DropdownMenuItem>

        <DropdownMenuItem @click="resetPassword">
          <component
            :is="TABLE_ACTION_ICONS.details"
            :class="tableActionMenuIconClass"
            aria-hidden="true"
          />
          Reset password
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        <DropdownMenuItem
          :class="user.status === 'active' ? 'text-destructive focus:text-destructive' : undefined"
          @click="toggleStatus"
        >
          <component
            :is="TABLE_ACTION_ICONS.deactivate"
            :class="tableActionMenuIconClass"
            aria-hidden="true"
          />
          {{ user.status === 'active' ? 'Deactivate user' : 'Activate user' }}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
