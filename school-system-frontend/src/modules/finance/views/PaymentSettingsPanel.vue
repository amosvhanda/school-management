<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { CreditCard, Eye, RefreshCw, Save, Wallet } from '@lucide/vue'
import { cn } from '@/lib/utils'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { useToast } from '@/composables/useToast'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { useAcademicYearFilter } from '@/composables/useAcademicYearFilter'
import { schoolApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'

const toast = useToast()
const { school, loadSchool } = useSchoolProfile()
const { academicYear } = useAcademicYearFilter()

const loading = ref(true)
const saving = ref(false)
const activeTab = ref('general')

const general = reactive({
  support_email: '',
  settlement_account: '',
  accept_cash: true,
  accept_bank: true,
  accept_mobile: true,
  published: false,
})

const providers = ref([
  { id: 'cash', name: 'Counter & Cash', status: 'live', mode: 'Production', share: '35%' },
  { id: 'bank', name: 'Bank transfer', status: 'live', mode: 'Production', share: '40%' },
  { id: 'mobile', name: 'Mobile money', status: 'test', mode: 'Sandbox', share: '25%' },
])

const rules = ref([
  {
    id: 1,
    condition: 'Payment fails twice at the gateway',
    action: 'Offer counter payment and email the parent',
    detail: 'Parents can complete the fee at the office when online collection fails.',
    enabled: true,
  },
  {
    id: 2,
    condition: 'Amount is above the school threshold',
    action: 'Route to bank transfer or net banking',
    detail: 'Large payments skip card limits where configured.',
    enabled: true,
  },
  {
    id: 3,
    condition: 'Sibling discount is active',
    action: 'Apply the discount before the payment page loads',
    detail: 'Discounts from fee policy are reflected on the payable total.',
    enabled: true,
  },
  {
    id: 4,
    condition: 'Fee is more than 15 days overdue',
    action: 'Add the late fee to the payable amount',
    detail: 'Late fee rules from finance policy are evaluated at checkout.',
    enabled: false,
  },
])

const sidebarGroups = computed(() => [
  {
    title: 'Money in',
    items: [
      { id: 'general', label: 'Payment settings', badge: general.published ? 'Live' : 'Draft' },
      { id: 'providers', label: 'Payment gateways', badge: String(providers.value.length) },
      { id: 'rules', label: 'Business rules', badge: String(rules.value.length) },
    ],
  },
  {
    title: 'Billing',
    items: [
      { id: 'general', label: 'Receipt settings', badge: 'Set' },
      { id: 'general', label: 'Invoice settings', badge: 'Set' },
    ],
  },
])

function selectWorkspaceTab(tab: string) {
  activeTab.value = tab
}

const statusBadge = computed(() => (general.published ? 'Live' : 'Draft'))

async function load() {
  loading.value = true
  try {
    await loadSchool()
    const settings = (await schoolApi.settings()) as Record<string, Record<string, unknown>>
    const pay = settings?.payments ?? {}
    general.support_email = String(pay.support_email ?? school.value?.email ?? '')
    general.settlement_account = String(pay.settlement_account ?? '')
    general.accept_cash = pay.accept_cash !== false
    general.accept_bank = pay.accept_bank !== false
    general.accept_mobile = pay.accept_mobile !== false
    general.published = pay.published === true
  } catch (err) {
    toast.error('Could not load payment settings', getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function save(publish = false) {
  saving.value = true
  try {
    if (publish) general.published = true
    await schoolApi.updateSettings([
      { group: 'payments', key: 'support_email', value: general.support_email || null },
      { group: 'payments', key: 'settlement_account', value: general.settlement_account || null },
      { group: 'payments', key: 'accept_cash', value: general.accept_cash },
      { group: 'payments', key: 'accept_bank', value: general.accept_bank },
      { group: 'payments', key: 'accept_mobile', value: general.accept_mobile },
      { group: 'payments', key: 'published', value: general.published },
    ])
    toast.success(publish ? 'Payment settings published' : 'Payment settings saved')
  } catch (err) {
    toast.error('Save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <header class="space-y-3 rounded-2xl border border-border/70 bg-card p-5 shadow-[var(--shadow-soft)]">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-3">
          <span
            class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
            aria-hidden="true"
          >
            <Wallet class="size-5" />
          </span>
          <div class="min-w-0 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-lg font-semibold text-foreground">Payment Settings</h2>
              <Badge variant="secondary">{{ statusBadge }}</Badge>
            </div>
            <p class="max-w-2xl text-sm text-muted-foreground">
              How the school accepts money — providers, methods, limits and the rules that decide when a payment is accepted, retried or refused.
            </p>
            <p class="text-xs text-muted-foreground">
              Configuration status:
              <Badge variant="secondary" class="ml-1">{{ general.published ? 'Published · LIVE' : 'Draft' }}</Badge>
            </p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button type="button" variant="outline" size="sm" :disabled="loading" @click="load">
            <RefreshCw class="mr-1.5 size-4" aria-hidden="true" />
            Refresh
          </Button>
          <Button type="button" variant="outline" size="sm" :disabled="saving" @click="save(false)">
            Save changes
          </Button>
          <Button type="button" size="sm" :disabled="saving" @click="save(true)">
            Publish
          </Button>
        </div>
      </div>
    </header>

    <PageLoader v-if="loading" label="Loading payment settings" />

    <div v-else class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)]">
      <nav class="space-y-4 rounded-2xl border border-border/70 bg-card p-3 shadow-[var(--shadow-soft)]" aria-label="Payment settings sections">
        <div v-for="group in sidebarGroups" :key="group.title" class="space-y-1">
          <p class="px-2 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ group.title }}
          </p>
          <button
            v-for="item in group.items"
            :key="`${group.title}-${item.label}`"
            type="button"
            :class="cn(
              'flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-left text-sm transition-colors',
              activeTab === item.id ? 'bg-primary/10 font-medium text-primary' : 'text-muted-foreground hover:bg-muted/50',
            )"
            @click="selectWorkspaceTab(item.id)"
          >
            <span class="truncate">{{ item.label }}</span>
            <Badge variant="outline" class="shrink-0 text-[10px]">{{ item.badge }}</Badge>
          </button>
        </div>
      </nav>

      <Tabs v-model="activeTab" class="min-w-0 space-y-4">
      <TabsList class="grid w-full max-w-lg grid-cols-3">
        <TabsTrigger value="general">General</TabsTrigger>
        <TabsTrigger value="providers">Providers ({{ providers.length }})</TabsTrigger>
        <TabsTrigger value="rules">Business rules ({{ rules.length }})</TabsTrigger>
      </TabsList>

      <TabsContent value="general" class="space-y-4">
        <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-[var(--shadow-soft)]">
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <Label for="pay-institution">Institution</Label>
              <Input id="pay-institution" :model-value="school?.name ?? ''" readonly />
            </div>
            <div class="space-y-2">
              <Label for="pay-year">Financial year</Label>
              <Input id="pay-year" :model-value="academicYear" readonly />
            </div>
            <div class="space-y-2">
              <Label for="pay-currency">Base currency</Label>
              <Input id="pay-currency" :model-value="school?.currency ?? 'USD'" readonly />
            </div>
            <div class="space-y-2">
              <Label for="pay-settlement">Settlement account</Label>
              <Input
                id="pay-settlement"
                v-model="general.settlement_account"
                placeholder="Bank account label or reference"
              />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="pay-support">Support email on receipts</Label>
              <Input id="pay-support" v-model="general.support_email" type="email" autocomplete="email" />
            </div>
          </div>

          <div class="mt-6 space-y-3">
            <label class="flex items-center justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="text-sm font-medium">Accept cash at counter</span>
              <Checkbox
                :checked="general.accept_cash"
                @update:checked="(v: boolean | 'indeterminate') => (general.accept_cash = v === true)"
              />
            </label>
            <label class="flex items-center justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="text-sm font-medium">Accept bank transfer</span>
              <Checkbox
                :checked="general.accept_bank"
                @update:checked="(v: boolean | 'indeterminate') => (general.accept_bank = v === true)"
              />
            </label>
            <label class="flex items-center justify-between gap-4 rounded-xl border border-border/60 px-4 py-3">
              <span class="text-sm font-medium">Accept mobile money</span>
              <Checkbox
                :checked="general.accept_mobile"
                @update:checked="(v: boolean | 'indeterminate') => (general.accept_mobile = v === true)"
              />
            </label>
          </div>

          <div class="mt-6 flex justify-end">
            <Button type="button" :disabled="saving" @click="save(false)">
              <Save class="mr-2 size-4" aria-hidden="true" />
              Save general settings
            </Button>
          </div>
        </div>
      </TabsContent>

      <TabsContent value="providers" class="space-y-3">
        <div
          v-for="provider in providers"
          :key="provider.id"
          class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border/70 bg-card p-4"
        >
          <div class="flex items-center gap-3">
            <CreditCard class="size-5 text-primary" aria-hidden="true" />
            <div>
              <p class="font-medium text-foreground">{{ provider.name }}</p>
              <p class="text-xs text-muted-foreground">
                {{ provider.mode }} · {{ provider.share }} of collections
              </p>
            </div>
          </div>
          <Badge :variant="provider.status === 'live' ? 'success' : 'outline'">
            {{ provider.status === 'live' ? 'Live' : 'Test mode' }}
          </Badge>
        </div>
        <p class="text-sm text-muted-foreground" role="status">
          Gateway API keys and live provider configuration are managed on the server. Contact your platform admin to add Razorpay, Stripe, or local gateways.
        </p>
      </TabsContent>

      <TabsContent value="rules" class="space-y-3">
        <p class="text-sm text-muted-foreground">
          Evaluated in order. The first rule that matches decides the outcome unless a later rule stops the payment.
        </p>
        <article
          v-for="(rule, index) in rules"
          :key="rule.id"
          class="rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1 space-y-2">
              <p class="text-xs font-semibold tabular-nums text-muted-foreground">
                {{ String(index + 1).padStart(2, '0') }}
              </p>
              <p class="text-sm text-foreground">
                <span class="font-medium">{{ rule.condition }}</span>
                <span class="text-muted-foreground"> → </span>
                <span class="rounded-md bg-primary/10 px-1.5 py-0.5 text-primary">{{ rule.action }}</span>
              </p>
              <p class="text-xs text-muted-foreground">{{ rule.detail }}</p>
            </div>
            <div class="flex items-center gap-2">
              <Checkbox
                :checked="rule.enabled"
                :aria-label="`${rule.condition} rule`"
                @update:checked="(v: boolean | 'indeterminate') => (rule.enabled = v === true)"
              />
              <Button type="button" variant="ghost" size="sm" class="h-8">
                <Eye class="mr-1 size-4" aria-hidden="true" />
                Review
              </Button>
            </div>
          </div>
        </article>
      </TabsContent>
    </Tabs>
    </div>
  </div>
</template>
