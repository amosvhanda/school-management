<script setup lang="ts">
import { computed, h, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { ColumnDef } from '@tanstack/vue-table'
import { Megaphone, MoreHorizontal, Pencil, Plus, Trash2 } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { useToast } from '@/composables/useToast'
import { useFormSheetLoader } from '@/composables/useFormSheetLoader'
import { useListFilters } from '@/composables/useListFilters'
import { formatDate } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import {
  ANNOUNCEMENT_AUDIENCES,
  ANNOUNCEMENT_TYPES,
  announcementCreateDefaults,
  announcementFormFields,
  announcementFormSchema,
  audienceLabel,
  mapAnnouncementFormToPayload,
  mapAnnouncementRowToFormValues,
  typeLabel,
} from '@/modules/communications/announcement-form'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import { commsApi } from '@/services/api.service'

interface AnnouncementRow {
  id?: number
  title?: string
  message?: string
  type?: string
  target_audience?: string
  date?: string
  is_active?: boolean
  created_at?: string
}

const toast = useToast()
const route = useRoute()
const router = useRouter()

const rows = ref<AnnouncementRow[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const sheetOpen = ref(false)
const saving = ref(false)
const editing = ref<AnnouncementRow | null>(null)
const deleteTarget = ref<AnnouncementRow | null>(null)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const announcementFilters: ListFilterSchema[] = [
  {
    key: 'type',
    label: 'Type',
    type: 'select',
    placeholder: 'All types',
    options: [{ label: 'All types', value: '' }, ...ANNOUNCEMENT_TYPES.map((t) => ({ label: t.label, value: t.value }))],
  },
  {
    key: 'target_audience',
    label: 'Audience',
    type: 'select',
    placeholder: 'All audiences',
    options: [{ label: 'All audiences', value: '' }, ...ANNOUNCEMENT_AUDIENCES.map((a) => ({ label: a.label, value: a.value }))],
  },
  {
    key: 'is_active',
    label: 'Visibility',
    type: 'select',
    placeholder: 'All',
    options: [
      { label: 'All', value: '' },
      { label: 'Visible', value: 'true' },
      { label: 'Hidden', value: 'false' },
    ],
  },
]

const { values: filterValues, activeCount, clearAll } = useListFilters(
  computed(() => announcementFilters),
  { onChange: () => {} },
)

const { formLoading, prepareCreate, prepareEdit } = useFormSheetLoader(() => ({
  endpoint: '/announcements',
  formFields: announcementFormFields,
  setFormValues: (values) => { formResetValues.value = values },
  mapRowToValues: mapAnnouncementRowToFormValues,
  createDefaults: announcementCreateDefaults,
}))

const filteredRows = computed(() => {
  let list = rows.value
  const type = filterValues.value.type
  const audience = filterValues.value.target_audience
  const active = filterValues.value.is_active

  if (type) list = list.filter((r) => r.type === type)
  if (audience) list = list.filter((r) => r.target_audience === audience)
  if (active === 'true') list = list.filter((r) => r.is_active !== false)
  if (active === 'false') list = list.filter((r) => r.is_active === false)

  return list
})

const kpis = computed(() => ({
  total: rows.value.length,
  active: rows.value.filter((r) => r.is_active !== false).length,
  parents: rows.value.filter((r) => r.target_audience === 'parents' || r.target_audience === 'all').length,
  thisWeek: rows.value.filter((r) => {
    const d = String(r.date ?? r.created_at ?? '').slice(0, 10)
    const now = new Date()
    const weekAgo = new Date(now)
    weekAgo.setDate(now.getDate() - 7)
    return d >= weekAgo.toISOString().slice(0, 10)
  }).length,
}))

function typeVariant(type?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  switch (type) {
    case 'important': return 'secondary'
    case 'warning': return 'destructive'
    case 'success': return 'default'
    default: return 'outline'
  }
}

const columns = computed<ColumnDef<Record<string, unknown>>[]>(() => [
  {
    id: 'title',
    header: 'Title',
    cell: ({ row }) => {
      const item = row.original as unknown as AnnouncementRow
      return h('div', { class: 'max-w-xs' }, [
        h('p', { class: 'font-medium line-clamp-1' }, item.title ?? '—'),
        h('p', { class: 'text-xs text-muted-foreground line-clamp-1' }, item.message ?? ''),
      ])
    },
  },
  {
    id: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const type = (row.original as unknown as AnnouncementRow).type
      return h(Badge, { variant: typeVariant(type), class: 'capitalize' }, () => typeLabel(type))
    },
  },
  {
    id: 'target_audience',
    header: 'Audience',
    cell: ({ row }) => audienceLabel((row.original as unknown as AnnouncementRow).target_audience),
  },
  {
    id: 'date',
    header: 'Publish date',
    cell: ({ row }) => formatDate((row.original as unknown as AnnouncementRow).date),
  },
  {
    id: 'status',
    header: 'Status',
    cell: ({ row }) => {
      const active = (row.original as unknown as AnnouncementRow).is_active !== false
      return h(Badge, { variant: active ? 'default' : 'secondary' }, () => active ? 'Visible' : 'Hidden')
    },
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => {
      const item = row.original as unknown as AnnouncementRow
      return h(
        DropdownMenu,
        {},
        {
          default: () => [
            h(DropdownMenuTrigger, { asChild: true }, () =>
              h(Button, {
                variant: 'ghost',
                size: 'icon',
                class: 'h-8 w-8',
                'aria-label': `Actions for ${item.title}`,
              }, () => h(MoreHorizontal, { class: 'h-4 w-4' })),
            ),
            h(DropdownMenuContent, { align: 'end' }, () => [
              h(DropdownMenuItem, { onSelect: () => openEdit(item) }, () => [
                h(Pencil, { class: 'mr-2 h-4 w-4' }),
                'Edit',
              ]),
              h(DropdownMenuSeparator),
              h(DropdownMenuItem, {
                class: 'text-destructive focus:text-destructive',
                onSelect: () => { deleteTarget.value = item },
              }, () => [
                h(Trash2, { class: 'mr-2 h-4 w-4' }),
                'Delete',
              ]),
            ]),
          ],
        },
      )
    },
  },
])

const { table, globalFilter } = useDataTable({ data: filteredRows as never, columns })

async function load() {
  loading.value = true
  error.value = null
  try {
    rows.value = await commsApi.announcements.list({ limit: 200 }) as AnnouncementRow[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load announcements')
  } finally {
    loading.value = false
  }
}

async function openCreate() {
  editing.value = null
  formResetValues.value = announcementCreateDefaults()
  sheetOpen.value = true
  await nextTick()
  await prepareCreate()
}

async function openEdit(item: AnnouncementRow) {
  editing.value = item
  sheetOpen.value = true
  await nextTick()
  const record = await prepareEdit(item as unknown as Record<string, unknown>)
  editing.value = record as unknown as AnnouncementRow
}

async function confirmDelete() {
  if (!deleteTarget.value?.id) return
  try {
    await commsApi.announcements.remove(deleteTarget.value.id)
    toast.success('Announcement deleted')
    deleteTarget.value = null
    await load()
  } catch (err) {
    toast.error('Delete failed', getErrorMessage(err))
  }
}

async function onSubmit(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapAnnouncementFormToPayload(values)
    if (editing.value?.id) {
      await commsApi.announcements.update(editing.value.id, payload)
      toast.success('Announcement updated')
    } else {
      await commsApi.announcements.create(payload)
      toast.success('Announcement published', 'Parents and staff can now see it in their feeds.')
    }
    sheetOpen.value = false
    await load()
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error(editing.value ? 'Update failed' : 'Could not publish', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

watch(
  () => route.query.create,
  (create) => {
    if (create === '1' && !loading.value) {
      void openCreate()
      router.replace({ query: { ...route.query, create: undefined } })
    }
  },
)

onMounted(async () => {
  await load()
  if (route.query.create === '1') {
    await openCreate()
    router.replace({ query: { ...route.query, create: undefined } })
  }
})
</script>

<template>
  <PageShell
    title="Announcements"
    description="Broadcast one-way updates to parents, students, and staff. For two-way chat, use Messages."
   max-width="wide">
    <template #actions>
      <Button @click="openCreate">
        <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
        New announcement
      </Button>
    </template>

    <PageLoader v-if="loading && !rows.length" label="Loading announcements" />
    <ErrorState v-else-if="error && !rows.length" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Total"
          :value="String(kpis.total)"
          subtitle="All announcements"
          :icon="Megaphone"
        />
        <KpiCard
          title="Visible"
          :value="String(kpis.active)"
          subtitle="Currently shown to audience"
          :icon="Megaphone"
          accent="success"
        />
        <KpiCard
          title="Parent-facing"
          :value="String(kpis.parents)"
          subtitle="All or parents audience"
          :icon="Megaphone"
        />
        <KpiCard
          title="This week"
          :value="String(kpis.thisWeek)"
          subtitle="Published in last 7 days"
          :icon="Megaphone"
        />
      </div>

      <DataTable
        :table="table"
        :columns="columns"
        :global-filter="globalFilter"
        search-placeholder="Search announcements…"
        @update:global-filter="globalFilter = $event"
      >
        <template #filters>
          <ListFiltersBar
            v-model="filterValues"
            :filters="announcementFilters"
            :active-count="activeCount"
            @clear="clearAll"
          />
        </template>
      </DataTable>

      <p v-if="!filteredRows.length && rows.length" class="text-center text-sm text-muted-foreground">
        No announcements match your filters.
      </p>
      <p v-else-if="!rows.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
        No announcements yet. Click <strong>New announcement</strong> to broadcast your first update.
      </p>
    </template>
  </PageShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="sheetOpen"
    :title="editing ? 'Edit announcement' : 'New announcement'"
    :description="editing ? 'Update content or hide the announcement without deleting it.' : 'Parents and staff will see this in their portal feeds. They cannot reply to announcements.'"
    :fields="announcementFormFields"
    :schema="announcementFormSchema"
    :reset-values="formResetValues"
    :form-key="editing ? `announcement-edit-${editing.id}` : 'announcement-create'"
    :form-loading="formLoading"
    :saving="saving"
    :save-label="editing ? 'Save changes' : 'Publish announcement'"
    @submit="onSubmit"
  />

  <Dialog :open="!!deleteTarget" @update:open="(v) => !v && (deleteTarget = null)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Delete announcement?</DialogTitle>
        <DialogDescription>
          This permanently removes <strong>{{ deleteTarget?.title }}</strong>. Consider hiding it instead if you may need the record later.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="gap-2">
        <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
        <Button variant="destructive" @click="confirmDelete">Delete</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
