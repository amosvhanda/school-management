<script setup lang="ts">
import { computed } from 'vue'
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
  iconForActionLabel,
  tableActionIconClass,
  tableActionMenuIconClass,
} from '@/components/data-table/table-action-icons'

export interface TableRowMenuAction {
  label: string
  disabled?: boolean
  destructive?: boolean
  onSelect: () => void
}

const props = defineProps<{
  canEdit?: boolean
  canDelete?: boolean
  canView?: boolean
  /** Secondary / workflow actions — shown in the overflow menu. */
  actions?: TableRowMenuAction[]
  editLabel?: string
  deleteLabel?: string
  viewLabel?: string
}>()

const emit = defineEmits<{
  edit: []
  delete: []
  view: []
}>()

const menuActions = computed(() => props.actions ?? [])
const hasMenu = computed(() => menuActions.value.length > 0)
const hasPrimary = computed(
  () => Boolean(props.canEdit || props.canDelete || props.canView),
)
</script>

<template>
  <div
    v-if="hasPrimary || hasMenu"
    class="flex items-center justify-end gap-0.5"
  >
    <Button
      v-if="canView"
      type="button"
      variant="ghost"
      size="icon"
      class="size-8"
      :aria-label="viewLabel ?? 'View'"
      @click="emit('view')"
    >
      <component :is="TABLE_ACTION_ICONS.view" :class="tableActionIconClass" aria-hidden="true" />
    </Button>

    <Button
      v-if="canEdit"
      type="button"
      variant="ghost"
      size="icon"
      class="size-8"
      :aria-label="editLabel ?? 'Edit'"
      @click="emit('edit')"
    >
      <component :is="TABLE_ACTION_ICONS.edit" :class="tableActionIconClass" aria-hidden="true" />
    </Button>

    <Button
      v-if="canDelete"
      type="button"
      variant="ghost"
      size="icon"
      class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
      :aria-label="deleteLabel ?? 'Delete'"
      @click="emit('delete')"
    >
      <component :is="TABLE_ACTION_ICONS.delete" :class="tableActionIconClass" aria-hidden="true" />
    </Button>

    <DropdownMenu v-if="hasMenu">
      <DropdownMenuTrigger as-child>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          class="size-8"
          aria-label="More actions"
        >
          <MoreHorizontal :class="tableActionIconClass" aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="min-w-[11rem]">
        <DropdownMenuItem
          v-for="(action, index) in menuActions"
          :key="`${action.label}-${index}`"
          :disabled="action.disabled"
          :class="action.destructive ? 'text-destructive focus:text-destructive' : undefined"
          @select="action.onSelect()"
        >
          <component
            :is="iconForActionLabel(action.label)"
            :class="tableActionMenuIconClass"
            aria-hidden="true"
          />
          {{ action.label }}
        </DropdownMenuItem>
        <DropdownMenuSeparator v-if="false" />
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
