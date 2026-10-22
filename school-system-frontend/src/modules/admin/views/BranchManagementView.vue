<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import {
  Building2,
  CalendarDays,
  CheckCircle2,
  Clock,
  Pencil,
  Plus,
  RefreshCw,
  Settings,
  Trash2,
} from '@lucide/vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
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
import { useToast } from '@/composables/useToast'
import { formatDate } from '@/lib/format'
import { getErrorMessage } from '@/lib/api-response'
import { cn } from '@/lib/utils'
import { badgeVariantForStatus } from '@/lib/ui-status'
import { platformApi } from '@/services/api.service'

type BranchNode = {
  id: number
  name: string
  code: string
  branch_type?: string | null
  status?: string | null
  updated_at?: string | null
  members_count?: number
  children?: BranchNode[]
}

const toast = useToast()
const loading = ref(true)
const error = ref<string | null>(null)
const saving = ref(false)
const createOpen = ref(false)
const flatBranches = ref<BranchNode[]>([])

const createForm = reactive({
  name: '',
  code: '',
  email: '',
  phone: '',
  address: '',
})

const CARD_ACCENTS = [
  'bg-primary/10 text-primary',
  'bg-chart-2/15 text-chart-2',
  'bg-chart-3/15 text-chart-3',
  'bg-chart-4/15 text-chart-4',
  'bg-chart-5/15 text-chart-5',
] as const

function flatten(nodes: BranchNode[] | undefined): BranchNode[] {
  if (!nodes?.length) return []
  const out: BranchNode[] = []
  for (const node of nodes) {
    out.push(node)
    out.push(...flatten(node.children))
  }
  return out
}

const kpis = computed(() => {
  const total = flatBranches.value.length
  const active = flatBranches.value.filter((b) => String(b.status ?? 'active').toLowerCase() === 'active').length
  const pending = flatBranches.value.filter((b) => {
    const s = String(b.status ?? '').toLowerCase()
    return s === 'pending' || s === 'draft' || s === 'suspended'
  }).length
  const monthStart = new Date()
  monthStart.setDate(1)
  monthStart.setHours(0, 0, 0, 0)
  const thisMonth = flatBranches.value.filter((b) => {
    if (!b.updated_at) return false
    return new Date(b.updated_at) >= monthStart
  }).length
  return { total, active, pending, thisMonth }
})

function statusLabel(status: string | null | undefined) {
  const s = String(status ?? 'active').toLowerCase()
  if (s === 'active') return 'Active'
  if (s === 'suspended' || s === 'inactive') return 'Inactive'
  if (s === 'pending' || s === 'draft') return 'Pending'
  return s.charAt(0).toUpperCase() + s.slice(1)
}

function cardAccent(index: number) {
  return CARD_ACCENTS[index % CARD_ACCENTS.length]
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const tree = (await platformApi.branchTree()) as {
      branches?: BranchNode[]
    }
    flatBranches.value = flatten(tree.branches ?? [])
  } catch (err) {
    error.value = getErrorMessage(err, 'Could not load branches')
    flatBranches.value = []
  } finally {
    loading.value = false
  }
}

async function submitCreate() {
  if (!createForm.name.trim() || !createForm.code.trim()) {
    toast.warning('Missing fields', 'Branch name and code are required.')
    return
  }
  saving.value = true
  try {
    await platformApi.createBranch({
      name: createForm.name.trim(),
      code: createForm.code.trim(),
      email: createForm.email.trim() || null,
      phone: createForm.phone.trim() || null,
      address: createForm.address.trim() || null,
      branch_type: 'branch',
    })
    toast.success('Branch created')
    createOpen.value = false
    createForm.name = ''
    createForm.code = ''
    createForm.email = ''
    createForm.phone = ''
    createForm.address = ''
    await load()
  } catch (err) {
    toast.error('Could not create branch', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Branch management"
    description="Campus and branch schools linked to your institution."
    :breadcrumbs="['Administration', 'Branch management']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" class="rounded-xl" :disabled="loading" @click="load">
        <RefreshCw class="mr-1.5 size-4" aria-hidden="true" />
        Refresh
      </Button>
      <Button type="button" class="rounded-xl" @click="createOpen = true">
        <Plus class="mr-1.5 size-4" aria-hidden="true" />
        Add Branch
      </Button>
    </template>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Branch summary">
      <KpiCard title="Total Records" :value="kpis.total.toLocaleString()" :icon="Settings" />
      <KpiCard title="Active" :value="kpis.active.toLocaleString()" :icon="CheckCircle2" accent="success" />
      <KpiCard title="Pending" :value="kpis.pending.toLocaleString()" :icon="Clock" accent="warning" />
      <KpiCard title="This Month" :value="kpis.thisMonth.toLocaleString()" :icon="CalendarDays" />
    </section>

    <PageLoader v-if="loading && !flatBranches.length" label="Loading branches" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <EmptyState
      v-else-if="!flatBranches.length"
      title="No branches yet"
      description="Create a branch campus to manage multiple sites under one institution."
    >
      <Button type="button" class="mt-4 rounded-xl" @click="createOpen = true">
        <Plus class="mr-1.5 size-4" aria-hidden="true" />
        Add Branch
      </Button>
    </EmptyState>

    <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="(branch, index) in flatBranches"
        :key="branch.id"
        class="flex flex-col rounded-2xl border border-border/70 bg-card shadow-[var(--shadow-soft)]"
      >
        <div class="flex items-start justify-between gap-3 p-4 pb-3">
          <span
            :class="cn('inline-flex size-11 items-center justify-center rounded-xl', cardAccent(index))"
            aria-hidden="true"
          >
            <Building2 class="size-5" />
          </span>
          <Badge :variant="badgeVariantForStatus(branch.status ?? 'active')" class="rounded-full capitalize">
            {{ statusLabel(branch.status) }}
          </Badge>
        </div>
        <div class="space-y-1 px-4">
          <h3 class="text-base font-semibold text-foreground">{{ branch.name }}</h3>
          <p class="text-xs text-muted-foreground">Reference {{ branch.code }}</p>
        </div>
        <dl class="mt-4 space-y-2 border-t border-border/50 px-4 py-3 text-sm">
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">Members</dt>
            <dd class="font-medium tabular-nums">{{ branch.members_count ?? 0 }}</dd>
          </div>
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">Updated</dt>
            <dd class="text-right text-xs text-muted-foreground">
              {{ branch.updated_at ? formatDate(branch.updated_at) : '—' }}
            </dd>
          </div>
        </dl>
        <div class="mt-auto flex items-center gap-2 border-t border-border/50 p-3">
          <Button type="button" variant="outline" class="h-9 flex-1 rounded-xl" disabled>
            <Pencil class="mr-1.5 size-4" aria-hidden="true" />
            Edit
          </Button>
          <Button
            type="button"
            variant="outline"
            size="icon"
            class="size-9 shrink-0 rounded-xl text-destructive"
            disabled
            aria-label="Delete branch"
          >
            <Trash2 class="size-4" aria-hidden="true" />
          </Button>
        </div>
      </article>
    </div>

    <Dialog v-model:open="createOpen">
      <DialogContent class="max-w-lg rounded-2xl">
        <DialogHeader>
          <DialogTitle>Add branch</DialogTitle>
          <DialogDescription>Create a new campus linked to your school.</DialogDescription>
        </DialogHeader>
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2 sm:col-span-2">
            <Label for="branch-name">Branch name</Label>
            <Input id="branch-name" v-model="createForm.name" autocomplete="organization" />
          </div>
          <div class="space-y-2">
            <Label for="branch-code">School code</Label>
            <Input id="branch-code" v-model="createForm.code" autocomplete="off" />
          </div>
          <div class="space-y-2">
            <Label for="branch-email">Email</Label>
            <Input id="branch-email" v-model="createForm.email" type="email" autocomplete="email" />
          </div>
          <div class="space-y-2">
            <Label for="branch-phone">Phone</Label>
            <Input id="branch-phone" v-model="createForm.phone" autocomplete="tel" />
          </div>
          <div class="space-y-2 sm:col-span-2">
            <Label for="branch-address">Address</Label>
            <Input id="branch-address" v-model="createForm.address" autocomplete="street-address" />
          </div>
        </div>
        <DialogFooter class="gap-2 sm:gap-0">
          <Button type="button" variant="outline" :disabled="saving" @click="createOpen = false">
            Cancel
          </Button>
          <Button type="button" :disabled="saving" @click="submitCreate">
            {{ saving ? 'Creating…' : 'Create branch' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
