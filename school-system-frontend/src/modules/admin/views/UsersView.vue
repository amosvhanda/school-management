<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { UserPlus } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import UserRowActions from '@/modules/admin/components/UserRowActions.vue'
import UserExtraPermissionsSheet from '@/modules/admin/components/UserExtraPermissionsSheet.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import PageShell from '@/components/layout/PageShell.vue'
import WorkspaceCard from '@/components/layout/WorkspaceCard.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { useToast } from '@/components/ui/toast/use-toast'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { getErrorMessage } from '@/lib/api-response'
import { usersApi, rolesApi } from '@/services/api.service'
import { moduleEndpoints } from '@/services'
import { userFormFields, userFormSchema } from '@/modules/admin/user-form'
import type { PermissionRecord, RoleRecord } from '@/modules/admin/types'

interface UserRow {
  id: number
  name: string
  email: string
  role: string
  status: string
  permission_ids?: number[]
}

const { toast } = useToast()
const rows = ref<UserRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const editing = ref<UserRow | null>(null)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)
const roleOptions = ref<Array<{ label: string; value: string }>>([])
const permissionCatalog = ref<PermissionRecord[]>([])
const permissionsOpen = ref(false)
const permissionsSaving = ref(false)
const permissionsLoading = ref(false)
const permissionsUser = ref<UserRow | null>(null)
const permissionsIds = ref<number[]>([])

const formFields = computed(() => {
  const base = editing.value ? userFormFields.filter((field) => field.name !== 'password') : userFormFields
  return base.map((field) =>
    field.name === 'role' ? { ...field, options: roleOptions.value } : field,
  )
})

const formKey = computed(() => (editing.value ? `user-edit-${editing.value.id}` : 'user-create'))

const { formLoading, prepareCreate, prepareEdit } = useFormSheetLoader(() => ({
  endpoint: moduleEndpoints.users,
  formFields: formFields.value,
  setFormValues: (values) => { formResetValues.value = values },
  mapRowToValues: (row) => ({
    name: String(row.name ?? ''),
    email: String(row.email ?? ''),
    role: String(row.role ?? ''),
  }),
}))

// Simple anchor declarations mapping directly down to template cell injection slots
const columns: ColumnDef<UserRow>[] = [
  { accessorKey: 'name', header: 'Name' },
  { accessorKey: 'email', header: 'Email' },
  { accessorKey: 'role', header: 'Role' },
  { accessorKey: 'status', header: 'Status' },
  { id: 'actions', header: '' },
]

const { table, globalFilter } = useDataTable({ data: rows as never, columns })

async function loadRoles() {
  try {
    const roles = await rolesApi.list() as RoleRecord[]
    roleOptions.value = roles.map((role) => ({ label: role.name, value: role.slug }))
  } catch {
    roleOptions.value = []
  }
}

async function loadPermissionCatalog() {
  try {
    permissionCatalog.value = await rolesApi.permissions() as PermissionRecord[]
  } catch {
    permissionCatalog.value = []
  }
}

async function load() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadRoles(), loadPermissionCatalog()])
    rows.value = await usersApi.list() as UserRow[]
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load users'
  } finally {
    loading.value = false
  }
}

async function openPermissions(user: UserRow) {
  permissionsUser.value = user
  permissionsOpen.value = true
  permissionsLoading.value = true
  try {
    if (!permissionCatalog.value.length) await loadPermissionCatalog()
    const record = await usersApi.get(user.id) as UserRow
    permissionsIds.value = Array.isArray(record.permission_ids) ? record.permission_ids : []
    permissionsUser.value = { ...user, ...record }
  } catch (err) {
    toast({
      title: 'Could not load access',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
    permissionsOpen.value = false
  } finally {
    permissionsLoading.value = false
  }
}

async function savePermissions(ids: number[]) {
  if (!permissionsUser.value) return
  permissionsSaving.value = true
  try {
    await usersApi.update(permissionsUser.value.id, { permission_ids: ids })
    toast({ title: 'Extra module access saved' })
    permissionsOpen.value = false
    await load()
  } catch (err) {
    toast({
      title: 'Save failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    permissionsSaving.value = false
  }
}

async function openCreate() {
  editing.value = null
  formResetValues.value = {}
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

async function openEdit(user: UserRow) {
  editing.value = user
  sheetOpen.value = true
  await nextTick()
  const record = await prepareEdit(user as unknown as Record<string, unknown>)
  editing.value = record as unknown as UserRow
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = { ...values } as Record<string, unknown>
    if (!payload.password) delete payload.password
    if (editing.value) {
      await usersApi.update(editing.value.id, payload)
      toast({ title: 'User details updated successfully' })
    } else {
      await usersApi.create(payload)
      toast({ title: 'User invitation sent successfully' })
    }
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast({
      title: 'Action failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell title="Users" description="Manage staff accounts, roles, and access" max-width="wide">
    <template #actions>
      <Button @click="openCreate">
        <UserPlus class="mr-2 h-4 w-4" aria-hidden="true" />
        Invite user
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading users…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <WorkspaceCard
      v-else
      title="Users"
      description="Manage staff accounts, roles, and access"
      meta-label="Records"
      :meta-value="rows.length"
      flush
    >
      <DataTable
        :framed="false"
        :table="table"
        :columns="columns"
        :global-filter="globalFilter"
        search-placeholder="Search users…"
        @update:global-filter="globalFilter = $event"
      >
        <!-- Clean, readable custom column template structures -->
        <template #cell-name="{ row }">
          <span class="font-medium text-sm text-foreground">{{ row.original.name }}</span>
        </template>

        <template #cell-email="{ row }">
          <span class="text-sm text-muted-foreground">{{ row.original.email }}</span>
        </template>

        <template #cell-role="{ row }">
          <Badge variant="outline" class="font-normal text-xs uppercase tracking-wider">
            {{ row.original.role }}
          </Badge>
        </template>

        <template #cell-status="{ row }">
          <Badge
            :variant="row.original.status === 'active' ? 'default' : 'secondary'"
            class="font-normal text-xs capitalize"
          >
            {{ row.original.status }}
          </Badge>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end pr-2">
            <UserRowActions
              :user="row.original"
              @edit="openEdit(row.original)"
              @permissions="openPermissions(row.original)"
              @refresh="load"
            />
          </div>
        </template>
      </DataTable>
    </WorkspaceCard>
  </PageShell>

  <FormSheet
    v-if="formFields"
    ref="formSheetRef"
    v-model:open="sheetOpen"
    :title="editing ? 'Edit user' : 'Invite user'"
    :fields="formFields"
    :schema="userFormSchema"
    :reset-values="formResetValues"
    :form-key="formKey"
    :form-loading="formLoading"
    :saving="saving"
    :save-label="editing ? 'Save changes' : 'Create user'"
    @submit="onSubmit"
  />

  <UserExtraPermissionsSheet
    v-model:open="permissionsOpen"
    :user-name="permissionsUser?.name ?? 'user'"
    :permissions="permissionCatalog"
    :permission-ids="permissionsIds"
    :saving="permissionsSaving"
    :loading="permissionsLoading"
    @submit="savePermissions"
  />
</template>
