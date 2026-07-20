<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Search } from '@lucide/vue'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { groupPermissions, resourceLabel, type PermissionRecord } from '@/modules/admin/types'

const model = defineModel<number[]>({ default: () => [] })

const props = defineProps<{
  permissions: PermissionRecord[]
  disabled?: boolean
}>()

const search = ref('')

const filteredPermissions = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return props.permissions
  return props.permissions.filter(
    (p) =>
      p.name.toLowerCase().includes(query)
      || p.slug.toLowerCase().includes(query)
      || p.resource.toLowerCase().includes(query)
      || (p.description ?? '').toLowerCase().includes(query),
  )
})

const grouped = computed(() => groupPermissions(filteredPermissions.value))
const selectedSet = computed(() => new Set(model.value))
const totalSelected = computed(() => model.value.length)

function isGroupFullySelected(ids: number[]) {
  return ids.length > 0 && ids.every((id) => selectedSet.value.has(id))
}

function isGroupPartiallySelected(ids: number[]) {
  const hasSome = ids.some((id) => selectedSet.value.has(id))
  const hasAll = isGroupFullySelected(ids)
  return hasSome && !hasAll
}

function handlePermissionUpdate(id: number, checked: boolean) {
  if (props.disabled) return
  const next = new Set(model.value)
  if (checked) next.add(id)
  else next.delete(id)
  model.value = [...next].sort((a, b) => a - b)
}

function handleGroupUpdate(ids: number[], checked: boolean) {
  if (props.disabled) return
  const next = new Set(model.value)
  for (const id of ids) {
    if (checked) next.add(id)
    else next.delete(id)
  }
  model.value = [...next].sort((a, b) => a - b)
}

function updateGroupSelection(ids: number[], value: boolean | 'indeterminate') {
  handleGroupUpdate(ids, value === true)
}

function updatePermissionSelection(id: number, value: boolean | 'indeterminate') {
  handlePermissionUpdate(id, value === true)
}

function createGroupSelectionHandler(ids: number[]) {
  return (value: boolean | 'indeterminate') => updateGroupSelection(ids, value)
}

function createPermissionSelectionHandler(id: number) {
  return (value: boolean | 'indeterminate') => updatePermissionSelection(id, value)
}

function selectAll() {
  model.value = props.permissions.map((p) => p.id).sort((a, b) => a - b)
}

function clearAll() {
  model.value = []
}

watch(
  () => props.permissions,
  (permissions) => {
    const valid = new Set(permissions.map((p) => p.id))
    model.value = model.value.filter((id) => valid.has(id))
  },
)
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <Label class="text-sm font-medium">Permissions</Label>
        <p class="text-xs text-muted-foreground">
          {{ totalSelected }} of {{ permissions.length }} selected
        </p>
      </div>
      <div class="flex flex-wrap gap-1">
        <!-- Replaced loose raw buttons with Shadcn link buttons -->
        <Button
          type="button"
          variant="link"
          class="h-auto p-0 text-xs font-medium text-primary hover:underline"
          :disabled="disabled"
          @click="selectAll"
        >
          Select all
        </Button>
        <span class="text-xs text-muted-foreground/50 self-center px-1" aria-hidden="true">|</span>
        <Button
          type="button"
          variant="link"
          class="h-auto p-0 text-xs font-medium text-muted-foreground hover:underline"
          :disabled="disabled"
          @click="clearAll"
        >
          Clear all
        </Button>
      </div>
    </div>

    <!-- Search Field Layout -->
    <div class="relative w-full">
      <Search
        class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
      <Input
        v-model="search"
        type="search"
        placeholder="Search permissions…"
        class="h-10 pl-9"
        autocomplete="off"
        :disabled="disabled"
      />
    </div>

    <!-- Scrollable Picker Workspace -->
    <div
      v-if="Object.keys(grouped).length"
      class="max-h-[min(420px,50vh)] space-y-4 overflow-y-auto rounded-lg border bg-background p-4"
      role="group"
      aria-label="Permission groups"
    >
      <section v-for="(items, resource) in grouped" :key="resource" class="space-y-2">
        <!-- Resource Section Header -->
        <div class="flex items-center gap-2 border-b border-muted/60 pb-2">
          <Checkbox
            :id="`group-${resource}`"
            :checked="isGroupFullySelected(items.map((p) => p.id)) ? true : isGroupPartiallySelected(items.map((p) => p.id)) ? 'indeterminate' : false"
            :disabled="disabled"
            @update:checked="createGroupSelectionHandler(items.map((p) => p.id))"
          />
          <Label :for="`group-${resource}`" class="cursor-pointer text-sm font-semibold tracking-tight">
            {{ resourceLabel(String(resource)) }}
          </Label>
        </div>

        <!-- Rules Sublist -->
        <ul class="space-y-1 pl-1">
          <li
            v-for="permission in items"
            :key="permission.id"
            class="flex items-start gap-3 rounded-md px-2 py-1.5 transition-colors hover:bg-muted/50"
          >
            <Checkbox
              :id="`perm-${permission.id}`"
              class="mt-0.5"
              :checked="selectedSet.has(permission.id)"
              :disabled="disabled"
              @update:checked="createPermissionSelectionHandler(permission.id)"
            />
            <div class="min-w-0 flex-1 space-y-0.5">
              <Label :for="`perm-${permission.id}`" class="cursor-pointer text-sm font-medium leading-none">
                {{ permission.name }}
              </Label>
              <p v-if="permission.description" class="text-xs text-muted-foreground leading-normal">
                {{ permission.description }}
              </p>
              <p class="font-mono text-[10px] text-muted-foreground/70">{{ permission.slug }}</p>
            </div>
          </li>
        </ul>
      </section>
    </div>

    <p v-else class="text-sm text-muted-foreground" role="status">
      No permissions match your search.
    </p>
  </div>
</template>
