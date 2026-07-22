<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useParentPortalScope } from '@/composables/useParentPortalScope'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatMoney } from '@/lib/finance-constants'
import { parentPortalApi } from '@/services/index'

interface StoreItem {
  id: number
  name?: string
  type?: string
  size?: string
  unit_price?: number | string
  currency?: string
  stock_quantity?: number
  description?: string
}

interface ChildOption {
  id: number
  fullName?: string
  full_name?: string
  balance?: number
  currency?: string
}

const toast = useToast()
const scopeStore = useParentPortalScope('store-child')
const loading = ref(true)
const error = ref<string | null>(null)
const saving = ref(false)
const items = ref<StoreItem[]>([])
const children = ref<ChildOption[]>([])
const studentId = ref('')
const quantities = ref<Record<number, number>>({})
const typeFilter = ref('uniform')

const selectedChild = computed(() =>
  children.value.find((c) => String(c.id) === studentId.value) ?? null,
)

const cartLines = computed(() =>
  items.value
    .map((item) => {
      const qty = Number(quantities.value[item.id] ?? 0)
      return { item, qty, lineTotal: qty * Number(item.unit_price ?? 0) }
    })
    .filter((line) => line.qty > 0),
)

const cartTotal = computed(() => cartLines.value.reduce((sum, line) => sum + line.lineTotal, 0))
const currency = computed(() =>
  String(selectedChild.value?.currency ?? items.value[0]?.currency ?? 'USD'),
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const [itemRows, childRows] = await Promise.all([
      parentPortalApi.storeItems({ type: typeFilter.value || undefined }) as Promise<StoreItem[]>,
      parentPortalApi.children() as Promise<ChildOption[]>,
    ])
    items.value = itemRows
    children.value = childRows
    studentId.value = scopeStore.resolveChildSelection(childRows, scopeStore.read(''), '')
    if (studentId.value) scopeStore.write(studentId.value)
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load school store')
  } finally {
    loading.value = false
  }
}

watch(typeFilter, () => {
  void load()
})

watch(studentId, (value) => {
  if (value) scopeStore.write(value)
})

async function placeOrder() {
  if (!studentId.value) {
    toast.error('Select a child', 'Choose which student this order is for.')
    return
  }
  if (!cartLines.value.length) {
    toast.error('Empty cart', 'Add at least one item.')
    return
  }
  saving.value = true
  try {
    await parentPortalApi.storeBuy({
      student_id: Number(studentId.value),
      items: cartLines.value.map((line) => ({
        item_id: line.item.id,
        quantity: line.qty,
      })),
    })
    toast.success('Order placed', 'The amount was added to the student fee account.')
    quantities.value = {}
    await load()
  } catch (err) {
    toast.error('Order failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

function setQty(itemId: number, raw: string | number, max: number) {
  const n = Math.max(0, Math.min(max, Number(raw) || 0))
  quantities.value = { ...quantities.value, [itemId]: n }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="School store"
    description="Order uniforms and school stock for your child. Orders are charged to their fee account."
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" as-child>
        <RouterLink to="/portal/children">My children</RouterLink>
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading store" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Order for</CardTitle>
          <CardDescription>Select the student who needs the items</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="store-child">Child</Label>
            <Select v-model="studentId">
              <SelectTrigger id="store-child">
                <SelectValue placeholder="Select child" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="child in children" :key="child.id" :value="String(child.id)">
                  {{ child.fullName ?? child.full_name ?? `Student #${child.id}` }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="store-type">Category</Label>
            <Select v-model="typeFilter">
              <SelectTrigger id="store-type">
                <SelectValue placeholder="Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="uniform">Uniforms</SelectItem>
                <SelectItem value="stationery">Stationery</SelectItem>
                <SelectItem value="book">Books</SelectItem>
                <SelectItem value="equipment">Equipment</SelectItem>
                <SelectItem value="other">Other</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <p v-if="selectedChild" class="text-sm text-muted-foreground sm:col-span-2">
            Current balance:
            {{ formatMoney(selectedChild.balance ?? 0, String(selectedChild.currency ?? currency)) }}
          </p>
        </CardContent>
      </Card>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="item in items" :key="item.id">
          <CardHeader class="pb-2">
            <div class="flex items-start justify-between gap-2">
              <CardTitle class="text-base">{{ item.name }}</CardTitle>
              <Badge variant="outline">{{ item.type }}</Badge>
            </div>
            <CardDescription>
              <span v-if="item.size">Size {{ item.size }} · </span>
              {{ formatMoney(item.unit_price, String(item.currency ?? currency)) }}
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-3">
            <p class="text-sm text-muted-foreground">
              {{ item.description || `${item.stock_quantity ?? 0} in stock` }}
            </p>
            <div class="space-y-2">
              <Label :for="`qty-${item.id}`">Quantity</Label>
              <Input
                :id="`qty-${item.id}`"
                type="number"
                min="0"
                :max="Number(item.stock_quantity ?? 0)"
                :model-value="quantities[item.id] ?? 0"
                @update:model-value="(v) => setQty(item.id, v as string | number, Number(item.stock_quantity ?? 0))"
              />
            </div>
          </CardContent>
        </Card>
      </div>

      <p v-if="!items.length" class="text-sm text-muted-foreground">
        No items available in this category right now.
      </p>

      <Card>
        <CardHeader>
          <CardTitle class="text-base">Cart total</CardTitle>
          <CardDescription>{{ cartLines.length }} line(s)</CardDescription>
        </CardHeader>
        <CardContent class="flex flex-wrap items-center justify-between gap-4">
          <p class="text-2xl font-semibold tabular-nums">{{ formatMoney(cartTotal, currency) }}</p>
          <Button :disabled="saving || !cartLines.length" @click="placeOrder">
            {{ saving ? 'Placing order…' : 'Charge to student account' }}
          </Button>
        </CardContent>
      </Card>
    </template>
  </PageShell>
</template>
