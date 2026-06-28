<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { useToast } from '@/components/ui/toast/use-toast'
import { getErrorMessage } from '@/lib/api-response'
import { usersApi } from '@/services/api.service'

const props = defineProps<{
  user: { id: number; status: string }
}>()

const emit = defineEmits<{
  edit: []
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
      description: `The user account state has been successfully updated.`,
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
    await usersApi.resetPassword(props.user.id, {})
    toast({
      title: 'Password reset successful',
      description: 'The user temporary password has been set to password123.',
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
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="h-8 w-8">
        <MoreHorizontal class="h-4 w-4" />
        <span class="sr-only">Open actions menu</span>
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-[160px]">
      <DropdownMenuItem @click="emit('edit')">
        Edit details
      </DropdownMenuItem>

      <DropdownMenuItem @click="resetPassword">
        Reset password
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <!-- Styled conditionally if deactivating a live user -->
      <DropdownMenuItem
        @click="toggleStatus"
        :class="user.status === 'active' ? 'text-destructive focus:text-destructive-foreground focus:bg-destructive' : ''"
      >
        {{ user.status === 'active' ? 'Deactivate user' : 'Activate user' }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
