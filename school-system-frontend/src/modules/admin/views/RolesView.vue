<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { KeyRound, Plus, Shield, Users } from 'lucide-vue-next'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import RoleFormSheet from '@/modules/admin/components/RoleFormSheet.vue'
import RolePermissionRulesPanel from '@/modules/admin/components/RolePermissionRulesPanel.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/components/ui/toast/use-toast'
import { getErrorMessage } from '@/lib/api-response'
import { groupPermissions, resourceLabel, type PermissionRecord, type RoleRecord } from '@/modules/admin/types'
import { rolesApi } from '@/services/api.service'

const { toast } = useToast()
const rows = ref<RoleRecord[]>([])
const permissions = ref<PermissionRecord[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const editing = ref<RoleRecord | null>(null)
const detailRole = ref<RoleRecord | null>(null)
const detailOpen = ref(false)
const deleteTarget = ref<RoleRecord | null>(null)
const deleting = ref(false)
const activeTab = ref('roles')
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const totalUsers = computed(() => rows.value.reduce((sum, role) => sum + (role.user_count ?? 0), 0))
const systemRoleCount = computed(() => rows.value.filter((r) => r.is_system).length)

// Columns configuration using descriptive ID anchors mapped into template blocks
const columns: ColumnDef<RoleRecord>[] = [
  {
    accessorKey: 'name',
    header: 'Role',
  },
  {
    accessorKey: 'slug',
    header: 'Slug',
  },
  {
    accessorKey: 'user_count',
    header: 'Users',
  },
  {
    id: 'permissions',
    header: 'Permissions',
  },
  {
    id: 'type',
    header: 'Type',
  },
  {
    id: 'actions',
    header: '',
  },
]

const { table, globalFilter } = useDataTable({ data: rows as never, columns })

const detailGroups = computed(() =>
  groupPermissions(detailRole.value?.permissions ?? []),
)

async function loadPermissions() {
  permissions.value = await rolesApi.permissions() as PermissionRecord[]
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [roleRows] = await Promise.all([
      rolesApi.list() as Promise<RoleRecord[]>,
      loadPermissions(),
    ])
    rows.value = roleRows
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load roles')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editing.value = null
  sheetOpen.value = true
}

async function openEdit(role: RoleRecord) {
  try {
    const full = await rolesApi.get(role.id) as RoleRecord
    editing.value = full
    sheetOpen.value = true
  } catch (err) {
    toast({
      title: 'Could not load role',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  }
}

function openDetail(role: RoleRecord) {
  detailRole.value = role
  detailOpen.value = true
}

async function onSubmit(payload: { name: string; description: string; permission_ids: number[] }) {
  saving.value = true
  try {
    if (editing.value) {
      await rolesApi.update(editing.value.id, payload)
      toast({ title: 'Role updated successfully' })
    } else {
      await rolesApi.create(payload)
      toast({ title: 'Role created successfully' })
    }
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast({
      title: 'Save failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    saving.value = false
  }
}

async function confirmDelete() {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await rolesApi.remove(deleteTarget.value.id)
    toast({ title: 'Role deleted successfully' })
    deleteTarget.value = null
    await load()
  } catch (err) {
    toast({
      title: 'Delete failed',
      description: getErrorMessage(err),
      variant: 'destructive',
    })
  } finally {
    deleting.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Roles & permissions"
    description="Define who can access what — assign permission groups to each role."
  >
    <template #actions>
      <Button v-if="activeTab === 'roles'" @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New role
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading roles…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <section aria-labelledby="roles-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="roles-kpis" class="sr-only">Role summary</h2>
        <KpiCard
          title="Roles"
          :value="String(rows.length)"
          :subtitle="`${systemRoleCount} system · ${rows.length - systemRoleCount} custom`"
          :icon="KeyRound"
        />
        <KpiCard
          title="Permissions"
          :value="String(permissions.length)"
          subtitle="Available in the catalog"
          :icon="Shield"
        />
        <KpiCard
          title="Users assigned"
          :value="String(totalUsers)"
          subtitle="Across all roles"
          :icon="Users"
          href="/admin/users"
        />
        <KpiCard
          title="Manage users"
          value="Open"
          subtitle="Assign roles to staff"
          :icon="Users"
          href="/admin/users"
        />
      </section>

      <Tabs v-model="activeTab" class="space-y-4">
        <TabsList aria-label="Roles administration">
          <TabsTrigger value="roles">Roles</TabsTrigger>
          <TabsTrigger value="rules">Permission rules</TabsTrigger>
        </TabsList>

        <TabsContent value="roles" class="space-y-4">
          <!-- Declarative UI cell rendering using standard template interpolation slots -->
          <DataTable
            :table="table"
            :columns="columns"
            :global-filter="globalFilter"
            search-placeholder="Search roles by name or slug…"
            @update:global-filter="globalFilter = $event"
          >
            <template #cell-name="{ row }">
              <div class="min-w-[10rem]">
                <p class="font-medium text-sm">{{ row.original.name }}</p>
                <p v-if="row.original.description" class="text-xs text-muted-foreground line-clamp-1 mt-0.5">
                  {{ row.original.description }}
                </p>
              </div>
            </template>

            <template #cell-slug="{ row }">
              <code class="text-xs font-mono rounded bg-muted px-1.5 py-0.5 tracking-tight border border-muted-foreground/10">
                {{ row.original.slug }}
              </code>
            </template>

            <template #cell-user_count="{ row }">
              <span v-if="!row.original.user_count" class="text-xs text-muted-foreground/70">0</span>
              <RouterLink
                v-else
                :to="{ path: '/admin/users', query: { role: row.original.slug } }"
                class="font-medium text-sm text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring rounded-sm"
              >
                {{ row.original.user_count }}
              </RouterLink>
            </template>

            <template #cell-permissions="{ row }">
              <Badge v-if="!row.original.permissions?.length" variant="outline" class="font-normal text-xs">
                None
              </Badge>
              <div v-else class="flex flex-wrap gap-1 max-w-xs">
                <Badge
                  v-for="p in row.original.permissions.slice(0, 2)"
                  :key="p.id"
                  variant="secondary"
                  class="font-normal text-xs"
                >
                  {{ p.name }}
                </Badge>
                <Badge v-if="row.original.permissions.length > 2" variant="outline" class="font-normal text-xs text-muted-foreground">
                  +{{ row.original.permissions.length - 2 }} more
                </Badge>
              </div>
            </template>

            <template #cell-type="{ row }">
              <Badge :variant="row.original.is_system ? 'default' : 'outline'" class="font-normal text-xs">
                {{ row.original.is_system ? 'System' : 'Custom' }}
              </Badge>
            </template>

            <template #cell-actions="{ row }">
              <div class="flex items-center gap-1 justify-end">
                <Button variant="ghost" size="sm" class="h-8 text-xs px-2.5" @click="openDetail(row.original)">View</Button>
                <Button variant="ghost" size="sm" class="h-8 text-xs px-2.5" @click="openEdit(row.original)">Edit</Button>
                <Button
                  v-if="!row.original.is_system"
                  variant="ghost"
                  size="sm"
                  class="h-8 text-xs px-2.5 text-destructive hover:bg-destructive/10 hover:text-destructive"
                  @click="deleteTarget = row.original"
                >
                  Delete
                </Button>
              </div>
            </template>
          </DataTable>
        </TabsContent>

        <TabsContent value="rules">
          <RolePermissionRulesPanel :permissions="permissions" />
        </TabsContent>
      </Tabs>
    </template>
  </PageShell>

  <RoleFormSheet
    ref="formSheetRef"
    v-model:open="sheetOpen"
    :role="editing"
    :permissions="permissions"
    :saving="saving"
    @submit="onSubmit"
  />

  <!-- Detail Dialog Modal workspace -->
  <Dialog v-model:open="detailOpen">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle class="text-lg font-semibold tracking-tight">{{ detailRole?.name }}</DialogTitle>
        <DialogDescription class="text-sm text-muted-foreground mt-1">
          {{ detailRole?.description || 'No descriptive overview provided for this authorization tier.' }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="detailRole" class="space-y-5 max-h-[50vh] overflow-y-auto pr-1">
        <dl class="grid grid-cols-2 gap-4 rounded-lg border border-muted/60 bg-muted/20 p-4 text-sm">
          <div>
            <dt class="text-xs text-muted-foreground font-medium">Identifier Slug</dt>
            <dd class="font-mono text-xs mt-1 text-foreground bg-background border px-1.5 py-0.5 rounded w-fit">{{ detailRole.slug }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground font-medium">Active Members</dt>
            <dd class="font-semibold text-foreground mt-1">{{ detailRole.user_count ?? 0 }} assigned</dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground font-medium">Context Classification</dt>
            <dd class="mt-1 text-foreground">{{ detailRole.is_system ? 'System Core' : 'User Created' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-muted-foreground font-medium">Mapped Rules</dt>
            <dd class="mt-1 text-foreground">{{ detailRole.permissions?.length ?? 0 }} active nodes</dd>
          </div>
        </dl>

        <div v-if="detailRole.permissions?.length" class="space-y-4">
          <section v-for="(items, resource) in detailGroups" :key="resource" class="space-y-2">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground/90">
              {{ resourceLabel(String(resource)) }}
            </h3>
            <ul class="space-y-1.5">
              <li
                v-for="permission in items"
                :key="permission.id"
                class="rounded-md border border-muted bg-card px-3 py-2 text-sm space-y-0.5"
              >
                <p class="font-medium text-foreground">{{ permission.name }}</p>
                <p v-if="permission.description" class="text-xs text-muted-foreground leading-normal">
                  {{ permission.description }}
                </p>
              </li>
            </ul>
          </section>
        </div>
        <p v-else class="text-sm text-muted-foreground italic" role="status">This role has no permissions assigned.</p>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" @click="detailOpen = false">Close</Button>
        <Button @click="detailOpen = false; openEdit(detailRole!)">Edit details</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Confirm Drop Role Modal -->
  <Dialog :open="Boolean(deleteTarget)" @update:open="(v) => { if (!v) deleteTarget = null }">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete role architecture</DialogTitle>
        <DialogDescription>
          Are you certain you want to purge the role archetype <strong class="text-foreground">“{{ deleteTarget?.name }}”</strong>? This action is permanent.
          <span v-if="(deleteTarget?.user_count ?? 0) > 0" class="mt-2 block text-xs rounded border border-destructive/30 bg-destructive/5 p-3 text-destructive font-medium">
            Warning: This authorization model is assigned to {{ deleteTarget?.user_count }} active users. Reassign those profiles before discarding this profile tier.
          </span>
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline" :disabled="deleting" @click="deleteTarget = null">Cancel</Button>
        <Button
          variant="destructive"
          :disabled="deleting || (deleteTarget?.user_count ?? 0) > 0"
          @click="confirmDelete"
        >
          {{ deleting ? 'Purging…' : 'Delete profile' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
