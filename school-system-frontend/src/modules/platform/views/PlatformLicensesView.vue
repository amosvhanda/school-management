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
const registerOpen = ref(false)
const registering = ref(false)
const provisionResult = ref<{
  schoolName: string
  adminEmail: string
  adminPassword: string
  licenseKey?: string | null
  licenseStatus?: string | null
} | null>(null)

const generateForm = ref({
  plan_type: 'annual',
  duration_months: '',
  customer_name: '',
  customer_email: '',
  notes: '',
})

const registerForm = ref({
  school_name: '',
  school_code: '',
  address: '',
  phone: '',
  email: '',
  currency: 'USD',
  contact_person: '',
  admin_name: '',
  admin_email: '',
  admin_password: '',
  admin_password_confirmation: '',
  generate_and_activate_license: true,
  plan_type: 'annual',
  duration_months: '',
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

function resetRegisterForm() {
  registerForm.value = {
    school_name: '',
    school_code: '',
    address: '',
    phone: '',
    email: '',
    currency: 'USD',
    contact_person: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
    generate_and_activate_license: true,
    plan_type: 'annual',
    duration_months: '',
  }
  provisionResult.value = null
}

function slugCodeFromName(name: string) {
  return name
    .trim()
    .toUpperCase()
    .replace(/[^A-Z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 20)
}

watch(
  () => registerForm.value.school_name,
  (name) => {
    if (!registerForm.value.school_code.trim() && name.trim()) {
      registerForm.value.school_code = slugCodeFromName(name)
    }
  },
)

async function submitRegister() {
  if (registerForm.value.admin_password !== registerForm.value.admin_password_confirmation) {
    toast.error('Passwords do not match')
    return
  }

  registering.value = true
  try {
    const payload: Record<string, unknown> = {
      school_name: registerForm.value.school_name,
      school_code: registerForm.value.school_code,
      address: registerForm.value.address || undefined,
      phone: registerForm.value.phone || undefined,
      email: registerForm.value.email || undefined,
      currency: registerForm.value.currency || 'USD',
      contact_person: registerForm.value.contact_person || registerForm.value.admin_name,
      contact_phone: registerForm.value.phone || undefined,
      contact_email: registerForm.value.email || registerForm.value.admin_email,
      admin_name: registerForm.value.admin_name,
      admin_email: registerForm.value.admin_email,
      admin_password: registerForm.value.admin_password,
      admin_password_confirmation: registerForm.value.admin_password_confirmation,
      generate_and_activate_license: registerForm.value.generate_and_activate_license,
    }

    if (registerForm.value.generate_and_activate_license) {
      payload.plan_type = registerForm.value.plan_type
      payload.customer_name = registerForm.value.school_name
      payload.customer_email = registerForm.value.admin_email
      if (registerForm.value.plan_type === 'custom' && registerForm.value.duration_months) {
        payload.duration_months = Number(registerForm.value.duration_months)
      }
    }

    const result = await platformApi.provisionSchool(payload)
    provisionResult.value = {
      schoolName: String(result.school?.name ?? registerForm.value.school_name),
      adminEmail: result.admin.email,
      adminPassword: registerForm.value.admin_password,
      licenseKey: result.license_key,
      licenseStatus: result.license_status,
    }
    toast.success('School registered')
    await loadAll()
    activeTab.value = 'schools'
  } catch (err) {
    toast.error('Could not register school', getErrorMessage(err))
  } finally {
    registering.value = false
  }
}

async function copyText(value: string, label: string) {
  await navigator.clipboard.writeText(value)
  toast.success(`${label} copied`)
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
      <Button variant="outline" @click="generateOpen = true; resetGenerateForm()">
        <Key class="size-4" aria-hidden="true" />
        Generate key
      </Button>
      <Button @click="registerOpen = true; resetRegisterForm()">
        <Plus class="size-4" aria-hidden="true" />
        Register school
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

    <Dialog v-model:open="registerOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Register school</DialogTitle>
          <DialogDescription>
            Create the school profile, seed defaults, and set up the school admin account so they can sign in and manage the school.
          </DialogDescription>
        </DialogHeader>

        <div v-if="provisionResult" class="space-y-4">
          <div class="rounded-lg border bg-muted/40 p-4 space-y-3">
            <p class="text-sm font-medium">{{ provisionResult.schoolName }} is ready.</p>
            <p class="text-sm text-muted-foreground">
              Share these login details with the school admin. License status:
              <span class="font-medium capitalize">{{ provisionResult.licenseStatus || 'none' }}</span>
            </p>
            <div class="space-y-2 text-sm">
              <div class="flex flex-wrap items-center justify-between gap-2 rounded bg-background p-3">
                <div>
                  <p class="text-xs text-muted-foreground">Admin email</p>
                  <p class="font-medium">{{ provisionResult.adminEmail }}</p>
                </div>
                <Button variant="outline" size="sm" @click="copyText(provisionResult.adminEmail, 'Email')">
                  <Copy class="size-4" aria-hidden="true" />
                  Copy
                </Button>
              </div>
              <div class="flex flex-wrap items-center justify-between gap-2 rounded bg-background p-3">
                <div>
                  <p class="text-xs text-muted-foreground">Temporary password</p>
                  <p class="font-medium font-mono">{{ provisionResult.adminPassword }}</p>
                </div>
                <Button variant="outline" size="sm" @click="copyText(provisionResult.adminPassword, 'Password')">
                  <Copy class="size-4" aria-hidden="true" />
                  Copy
                </Button>
              </div>
              <div
                v-if="provisionResult.licenseKey"
                class="flex flex-wrap items-center justify-between gap-2 rounded bg-background p-3"
              >
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground">Activated license key</p>
                  <p class="font-mono text-xs break-all">{{ provisionResult.licenseKey }}</p>
                </div>
                <Button variant="outline" size="sm" @click="copyText(provisionResult.licenseKey!, 'License key')">
                  <Copy class="size-4" aria-hidden="true" />
                  Copy
                </Button>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button @click="registerOpen = false">Done</Button>
          </DialogFooter>
        </div>

        <form v-else class="space-y-6" @submit.prevent="submitRegister">
          <section class="space-y-4" aria-labelledby="school-profile-heading">
            <h3 id="school-profile-heading" class="text-sm font-semibold">School profile</h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2 sm:col-span-2">
                <Label for="school_name">School name</Label>
                <Input id="school_name" v-model="registerForm.school_name" required autocomplete="organization" />
              </div>
              <div class="space-y-2">
                <Label for="school_code">School code</Label>
                <Input id="school_code" v-model="registerForm.school_code" required />
              </div>
              <div class="space-y-2">
                <Label for="currency">Currency</Label>
                <Select v-model="registerForm.currency">
                  <SelectTrigger id="currency">
                    <SelectValue placeholder="Currency" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="USD">USD</SelectItem>
                    <SelectItem value="ZWG">ZWG</SelectItem>
                    <SelectItem value="ZAR">ZAR</SelectItem>
                    <SelectItem value="GBP">GBP</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-2 sm:col-span-2">
                <Label for="address">Address</Label>
                <Input id="address" v-model="registerForm.address" autocomplete="street-address" />
              </div>
              <div class="space-y-2">
                <Label for="school_phone">Phone</Label>
                <Input id="school_phone" v-model="registerForm.phone" type="tel" autocomplete="tel" />
              </div>
              <div class="space-y-2">
                <Label for="school_email">School email</Label>
                <Input id="school_email" v-model="registerForm.email" type="email" autocomplete="email" />
              </div>
              <div class="space-y-2 sm:col-span-2">
                <Label for="contact_person">Contact person</Label>
                <Input id="contact_person" v-model="registerForm.contact_person" />
              </div>
            </div>
          </section>

          <section class="space-y-4" aria-labelledby="school-admin-heading">
            <h3 id="school-admin-heading" class="text-sm font-semibold">School admin account</h3>
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2 sm:col-span-2">
                <Label for="admin_name">Admin full name</Label>
                <Input id="admin_name" v-model="registerForm.admin_name" required autocomplete="name" />
              </div>
              <div class="space-y-2 sm:col-span-2">
                <Label for="admin_email">Admin email (login)</Label>
                <Input id="admin_email" v-model="registerForm.admin_email" type="email" required autocomplete="email" />
              </div>
              <div class="space-y-2">
                <Label for="admin_password">Password</Label>
                <Input
                  id="admin_password"
                  v-model="registerForm.admin_password"
                  type="password"
                  required
                  autocomplete="new-password"
                />
              </div>
              <div class="space-y-2">
                <Label for="admin_password_confirmation">Confirm password</Label>
                <Input
                  id="admin_password_confirmation"
                  v-model="registerForm.admin_password_confirmation"
                  type="password"
                  required
                  autocomplete="new-password"
                />
              </div>
            </div>
          </section>

          <section class="space-y-4" aria-labelledby="school-license-heading">
            <h3 id="school-license-heading" class="text-sm font-semibold">License</h3>
            <label class="flex items-start gap-3 rounded-lg border p-3 text-sm">
              <input
                v-model="registerForm.generate_and_activate_license"
                type="checkbox"
                class="mt-1 size-4 rounded border"
              >
              <span>
                <span class="font-medium">Generate and activate a license now</span>
                <span class="mt-1 block text-muted-foreground">
                  Recommended so the school admin can sign in and start managing immediately.
                </span>
              </span>
            </label>
            <div v-if="registerForm.generate_and_activate_license" class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-2">
                <Label for="register_plan_type">Plan type</Label>
                <Select v-model="registerForm.plan_type">
                  <SelectTrigger id="register_plan_type">
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
              <div v-if="registerForm.plan_type === 'custom'" class="space-y-2">
                <Label for="register_duration_months">Duration (months)</Label>
                <Input
                  id="register_duration_months"
                  v-model="registerForm.duration_months"
                  type="number"
                  min="1"
                  max="120"
                  required
                />
              </div>
            </div>
          </section>

          <DialogFooter>
            <Button type="button" variant="outline" @click="registerOpen = false">Cancel</Button>
            <Button type="submit" :disabled="registering">
              {{ registering ? 'Registering…' : 'Register school' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

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
