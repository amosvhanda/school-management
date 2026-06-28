<script setup lang="ts">
import { computed, h, onMounted, ref, watch } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import {
  Building2,
  Copy,
  Key,
  Plus,
  RefreshCw,
  School,
  ShieldCheck,
  ShieldOff,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DataTable from '@/components/data-table/DataTable.vue'
import ListFiltersBar from '@/components/data-table/ListFiltersBar.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useDataTable } from '@/components/data-table/useDataTable'
import { useListFilters } from '@/composables/useListFilters'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { applyClientFilters } from '@/lib/list-filter-utils'
import { statusColumn, textColumn } from '@/modules/shared/columns'
import type { ListFilterSchema } from '@/modules/shared/list-filters'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import {
  platformApi,
  type LicenseKeyRow,
  type PlatformLicenseSummary,
  type SchoolLicenseRow,
} from '@/services/api.service'

const toast = useToast()
const loading = ref(true)
const error = ref<string | null>(null)
const activeTab = ref('schools')
const summary = ref<PlatformLicenseSummary | null>(null)
const schools = ref<SchoolLicenseRow[]>([])
const keys = ref<LicenseKeyRow[]>([])
const generateOpen = ref(false)
const generatedKey = ref<string | null>(null)
const generating = ref(false)
const revokingId = ref<number | null>(null)

const generateForm = ref({
  plan_type: 'annual',
  duration_months: '',
  customer_name: '',
  customer_email: '',
  notes: '',
})

const schoolFilters: ListFilterSchema[] = [
  {
    key: 'license_status',
    label: 'License',
    type: 'select',
    options: [
      { label: 'All schools', value: '' },
      { label: 'Licensed', value: 'licensed' },
      { label: 'No license', value: 'unlicensed' },
      { label: 'Expired', value: 'expired' },
    ],
  },
]

const keyFilters: ListFilterSchema[] = [
  {
    key: 'status',
    label: 'Status',
    type: 'select',
    options: [
      { label: 'All keys', value: '' },
      { label: 'Unused', value: 'unused' },
      { label: 'Active', value: 'active' },
      { label: 'Expired', value: 'expired' },
      { label: 'Revoked', value: 'revoked' },
    ],
  },
  {
    key: 'assignment',
    label: 'Assignment',
    type: 'select',
    options: [
      { label: 'All', value: '' },
      { label: 'Unassigned', value: 'unassigned' },
      { label: 'Assigned to school', value: 'assigned' },
    ],
  },
]

const {
  values: schoolFilterValues,
  activeCount: schoolFilterCount,
  buildParams: buildSchoolParams,
  clearAll: clearSchoolFilters,
} = useListFilters(computed(() => schoolFilters), {
  onChange: () => { void loadSchools() },
})

const {
  values: keyFilterValues,
  activeCount: keyFilterCount,
  buildParams: buildKeyParams,
  clearAll: clearKeyFilters,
} = useListFilters(computed(() => keyFilters), {
  onChange: () => { void loadKeys() },
})

function planLabel(value?: string | null) {
  if (!value) return '—'
  return value.charAt(0).toUpperCase() + value.slice(1)
}

function schoolNameColumn(): ColumnDef<Record<string, unknown>> {
  return {
    id: 'school-name',
    header: 'School',
    cell: ({ row }) => {
      const record = row.original as unknown as SchoolLicenseRow
      return h('div', { class: 'space-y-0.5' }, [
        h('p', { class: 'font-medium leading-none' }, record.name),
        h('p', { class: 'text-xs text-muted-foreground' }, record.code),
      ])
    },
  }
}

function licenseStatusColumn(): ColumnDef<Record<string, unknown>> {
  return {
    id: 'license-state',
    header: 'License',
    cell: ({ row }) => {
      const record = row.original as unknown as SchoolLicenseRow
      const status = record.license_state?.status ?? record.license_status ?? 'none'
      const variant = status === 'active'
        ? 'default'
        : status === 'grace'
          ? 'outline'
          : status === 'none'
            ? 'secondary'
            : 'destructive'
      return h(Badge, { variant }, () => status)
    },
  }
}

function schoolColumn(): ColumnDef<Record<string, unknown>> {
  return {
    id: 'assigned-school',
    header: 'School',
    cell: ({ row }) => {
      const record = row.original as unknown as LicenseKeyRow
      if (!record.school) {
        return h(Badge, { variant: 'outline' }, () => 'Unassigned')
      }
      return h('div', { class: 'space-y-0.5' }, [
        h('p', { class: 'font-medium leading-none' }, record.school.name),
        h('p', { class: 'text-xs text-muted-foreground' }, record.school.code),
      ])
    },
  }
}

const schoolColumns: ColumnDef<Record<string, unknown>>[] = [
  schoolNameColumn(),
  textColumn('Contact', 'email'),
  {
    accessorKey: 'users_count',
    header: 'Users',
    cell: ({ row }) => String((row.original as unknown as SchoolLicenseRow).users_count ?? 0),
  },
  licenseStatusColumn(),
  {
    id: 'plan',
    header: 'Plan',
    cell: ({ row }) => planLabel((row.original as unknown as SchoolLicenseRow).license_plan),
  },
  {
    id: 'expires',
    header: 'Expires',
    cell: ({ row }) => {
      const value = (row.original as unknown as SchoolLicenseRow).license_expires_at
      return value ? formatDateTime(value) : 'Lifetime / none'
    },
  },
  {
    id: 'active-key',
    header: 'Active key',
    cell: ({ row }) => {
      const prefix = (row.original as unknown as SchoolLicenseRow).active_key?.key_prefix
      return prefix ? h('code', { class: 'text-xs' }, prefix) : '—'
    },
  },
]

const keyColumns = computed<ColumnDef<Record<string, unknown>>[]>(() => {
  const cols: ColumnDef<Record<string, unknown>>[] = [
    {
      accessorKey: 'key_prefix',
      header: 'Key',
      cell: ({ row }) => h('code', { class: 'text-xs' }, String(row.getValue('key_prefix') ?? '—')),
    },
    {
      id: 'plan_type',
      header: 'Plan',
      cell: ({ row }) => planLabel((row.original as unknown as LicenseKeyRow).plan_type),
    },
    statusColumn('Status', 'status'),
    schoolColumn(),
    textColumn('Customer', 'customer_name'),
    {
      id: 'activated_at',
      header: 'Activated',
      cell: ({ row }) => {
        const value = (row.original as unknown as LicenseKeyRow).activated_at
        return value ? formatDateTime(value) : '—'
      },
    },
    {
      id: 'expires_at',
      header: 'Expires',
      cell: ({ row }) => {
        const value = (row.original as unknown as LicenseKeyRow).expires_at
        return value ? formatDateTime(value) : '—'
      },
    },
  ]

  cols.push({
    id: 'key-actions',
    header: '',
    cell: ({ row }) => {
      const record = row.original as unknown as LicenseKeyRow
      if (!['unused', 'active'].includes(record.status)) return null
      return h(
        Button,
        {
          size: 'sm',
          variant: 'outline',
          disabled: revokingId.value === record.id,
          onClick: () => revokeKey(record),
        },
        () => 'Revoke',
      )
    },
  })

  return cols
})

const filteredKeys = computed(() => {
  let rows = keys.value as unknown as Record<string, unknown>[]
  const assignment = keyFilterValues.value.assignment
  if (assignment === 'unassigned') {
    rows = rows.filter((row) => !(row as unknown as LicenseKeyRow).school)
  } else if (assignment === 'assigned') {
    rows = rows.filter((row) => Boolean((row as unknown as LicenseKeyRow).school))
  }
  return applyClientFilters(rows, keyFilters.filter((f) => f.key !== 'assignment'), keyFilterValues.value)
})

const schoolTableRows = computed(() => schools.value as unknown as Record<string, unknown>[])
const keyTableRows = computed(() => filteredKeys.value)

const {
  table: schoolTable,
  globalFilter: schoolSearch,
} = useDataTable({ data: schoolTableRows, columns: schoolColumns })

const {
  table: keyTable,
  globalFilter: keySearch,
} = useDataTable({ data: keyTableRows, columns: keyColumns })

let schoolSearchTimer: ReturnType<typeof setTimeout> | undefined

watch(schoolSearch, () => {
  clearTimeout(schoolSearchTimer)
  schoolSearchTimer = setTimeout(() => { void loadSchools() }, 350)
})

async function loadSchools() {
  const params = buildSchoolParams()
  if (schoolSearch.value.trim()) params.search = schoolSearch.value.trim()
  const payload = await platformApi.schoolsOverview(params)
  schools.value = payload?.schools ?? []
  if (payload?.summary) summary.value = payload.summary
}

async function loadKeys() {
  const params = buildKeyParams()
  const payload = await platformApi.licenseOverview(params)
  keys.value = payload?.keys ?? []
  if (payload?.summary) summary.value = payload.summary
}

async function loadAll() {
  loading.value = true
  error.value = null
  try {
    await Promise.all([loadSchools(), loadKeys()])
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load license data')
  } finally {
    loading.value = false
  }
}

async function revokeKey(record: LicenseKeyRow) {
  revokingId.value = record.id
  try {
    await platformApi.revokeLicense(record.id)
    toast.success('License key revoked')
    await loadAll()
  } catch (err) {
    toast.error('Revoke failed', getErrorMessage(err))
  } finally {
    revokingId.value = null
  }
}

function resetGenerateForm() {
  generateForm.value = {
    plan_type: 'annual',
    duration_months: '',
    customer_name: '',
    customer_email: '',
    notes: '',
  }
  generatedKey.value = null
}

async function submitGenerate() {
  generating.value = true
  try {
    const payload: Record<string, unknown> = {
      plan_type: generateForm.value.plan_type,
      customer_name: generateForm.value.customer_name || undefined,
      customer_email: generateForm.value.customer_email || undefined,
      notes: generateForm.value.notes || undefined,
    }
    if (generateForm.value.plan_type === 'custom' && generateForm.value.duration_months) {
      payload.duration_months = Number(generateForm.value.duration_months)
    }
    const result = await platformApi.generateLicense(payload)
    generatedKey.value = result.license_key
    toast.success('License key generated')
    await loadAll()
  } catch (err) {
    toast.error('Generate failed', getErrorMessage(err))
  } finally {
    generating.value = false
  }
}

async function copyGeneratedKey() {
  if (!generatedKey.value) return
  await navigator.clipboard.writeText(generatedKey.value)
  toast.success('License key copied')
}

onMounted(loadAll)
</script>

<template>
  <PageShell
    title="Licenses"
    description="All registered schools and license keys — see who is licensed, unlicensed, or expired."
  >
    <template #actions>
      <Button variant="outline" :disabled="loading" @click="loadAll">
        <RefreshCw class="size-4" aria-hidden="true" />
        Refresh
      </Button>
      <Button @click="generateOpen = true; resetGenerateForm()">
        <Plus class="size-4" aria-hidden="true" />
        Generate key
      </Button>
    </template>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="loadAll" />

    <div v-else class="space-y-6">
      <section aria-labelledby="license-kpis" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <h2 id="license-kpis" class="sr-only">License summary</h2>
        <KpiCard
          title="Registered schools"
          :value="String(summary?.schools.total ?? schools.length)"
          subtitle="All schools on the platform"
          :icon="Building2"
        />
        <KpiCard
          title="Licensed"
          :value="String(summary?.schools.licensed ?? 0)"
          :subtitle="`${summary?.schools.unlicensed ?? 0} without license`"
          :icon="ShieldCheck"
          accent="success"
        />
        <KpiCard
          title="Unlicensed"
          :value="String(summary?.schools.unlicensed ?? 0)"
          :subtitle="`${summary?.schools.expired ?? 0} expired`"
          :icon="ShieldOff"
          accent="warning"
        />
        <KpiCard
          title="License keys"
          :value="String(summary?.keys.total ?? keys.length)"
          :subtitle="`${summary?.keys.unused ?? 0} unused · ${summary?.keys.active ?? 0} active`"
          :icon="Key"
        />
      </section>

      <Tabs v-model="activeTab">
        <TabsList aria-label="License views">
          <TabsTrigger value="schools">
            <School class="size-4" aria-hidden="true" />
            Schools
          </TabsTrigger>
          <TabsTrigger value="keys">
            <Key class="size-4" aria-hidden="true" />
            License keys
          </TabsTrigger>
        </TabsList>

        <TabsContent value="schools" class="space-y-4">
          <DataTable
            :table="schoolTable"
            :columns="schoolColumns"
            :global-filter="schoolSearch"
            search-placeholder="Search schools by name, code, or email…"
            @update:global-filter="schoolSearch = $event"
          >
            <template #filters>
              <ListFiltersBar
                v-model="schoolFilterValues"
                :filters="schoolFilters"
                :active-count="schoolFilterCount"
                @clear="clearSchoolFilters"
              />
            </template>
          </DataTable>
        </TabsContent>

        <TabsContent value="keys" class="space-y-4">
          <DataTable
            :table="keyTable"
            :columns="keyColumns"
            :global-filter="keySearch"
            search-placeholder="Search license keys…"
            @update:global-filter="keySearch = $event"
          >
            <template #filters>
              <ListFiltersBar
                v-model="keyFilterValues"
                :filters="keyFilters"
                :active-count="keyFilterCount"
                @clear="clearKeyFilters"
              />
            </template>
          </DataTable>
        </TabsContent>
      </Tabs>
    </div>

    <Dialog v-model:open="generateOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Generate license key</DialogTitle>
          <DialogDescription>
            Create a new key for a school to activate during registration or renewal.
          </DialogDescription>
        </DialogHeader>

        <div v-if="generatedKey" class="space-y-3 rounded-lg border bg-muted/40 p-4">
          <p class="text-sm font-medium">Copy this key now — it cannot be retrieved again.</p>
          <code class="block break-all rounded bg-background p-3 text-sm">{{ generatedKey }}</code>
          <Button variant="outline" class="w-full" @click="copyGeneratedKey">
            <Copy class="size-4" aria-hidden="true" />
            Copy key
          </Button>
        </div>

        <form v-else class="space-y-4" @submit.prevent="submitGenerate">
          <div class="space-y-2">
            <Label for="plan_type">Plan type</Label>
            <Select v-model="generateForm.plan_type">
              <SelectTrigger id="plan_type">
                <SelectValue placeholder="Select plan" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="lifetime">Lifetime</SelectItem>
                <SelectItem value="monthly">Monthly</SelectItem>
                <SelectItem value="quarterly">Quarterly</SelectItem>
                <SelectItem value="annual">Annual</SelectItem>
                <SelectItem value="custom">Custom</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div v-if="generateForm.plan_type === 'custom'" class="space-y-2">
            <Label for="duration_months">Duration (months)</Label>
            <Input
              id="duration_months"
              v-model="generateForm.duration_months"
              type="number"
              min="1"
              max="120"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="customer_name">Customer / school name</Label>
            <Input id="customer_name" v-model="generateForm.customer_name" autocomplete="organization" />
          </div>

          <div class="space-y-2">
            <Label for="customer_email">Customer email</Label>
            <Input id="customer_email" v-model="generateForm.customer_email" type="email" autocomplete="email" />
          </div>

          <div class="space-y-2">
            <Label for="notes">Notes</Label>
            <Input id="notes" v-model="generateForm.notes" />
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" @click="generateOpen = false">Cancel</Button>
            <Button type="submit" :disabled="generating">
              {{ generating ? 'Generating…' : 'Generate key' }}
            </Button>
          </DialogFooter>
        </form>

        <DialogFooter v-if="generatedKey">
          <Button @click="generateOpen = false">Done</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
