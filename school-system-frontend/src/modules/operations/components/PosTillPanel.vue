<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { operationsApi } from '@/services/api.service'
import { getErrorMessage, unwrapList } from '@/lib/api-response'
import { toast } from 'vue-sonner'

type InventoryItem = {
  id: number
  name: string
  sku?: string | null
  unit_price?: number | string | null
  quantity_on_hand?: number | null
  stock?: number | null
}

type CartLine = {
  item_id: number
  name: string
  quantity: number
  unit_price: number
}

const items = ref<InventoryItem[]>([])
const cart = ref<CartLine[]>([])
const loading = ref(true)
const busy = ref(false)
const paymentMethod = ref('cash')
const studentId = ref('')

const total = computed(() =>
  cart.value.reduce((sum, line) => sum + line.quantity * line.unit_price, 0),
)

function priceOf(item: InventoryItem) {
  return Number(item.unit_price ?? 0)
}

function stockOf(item: InventoryItem) {
  return Number(item.quantity_on_hand ?? item.stock ?? 0)
}

async function loadItems() {
  loading.value = true
  try {
    const rows = await operationsApi.inventory.items.list()
    items.value = unwrapList<InventoryItem>(rows) as InventoryItem[]
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not load inventory items'))
  } finally {
    loading.value = false
  }
}

function addToCart(item: InventoryItem) {
  const existing = cart.value.find((line) => line.item_id === item.id)
  if (existing) {
    existing.quantity += 1
    return
  }
  cart.value.push({
    item_id: item.id,
    name: item.name,
    quantity: 1,
    unit_price: priceOf(item),
  })
}

function updateQty(itemId: number, quantity: number) {
  const line = cart.value.find((l) => l.item_id === itemId)
  if (!line) return
  line.quantity = Math.max(1, Math.floor(quantity) || 1)
}

function removeLine(itemId: number) {
  cart.value = cart.value.filter((l) => l.item_id !== itemId)
}

async function checkout() {
  if (!cart.value.length) {
    toast.error('Add items to the till first')
    return
  }
  busy.value = true
  try {
    await operationsApi.inventory.sales.create({
      payment_method: paymentMethod.value,
      student_id: studentId.value ? Number(studentId.value) : null,
      items: cart.value.map((line) => ({
        item_id: line.item_id,
        quantity: line.quantity,
      })),
    })
    toast.success('Sale recorded')
    cart.value = []
    studentId.value = ''
    await loadItems()
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not complete sale'))
  } finally {
    busy.value = false
  }
}

onMounted(() => {
  void loadItems()
})
</script>

<template>
  <Card class="border-border/70">
    <CardHeader>
      <CardTitle class="text-base">Point of sale till</CardTitle>
      <CardDescription>
        Multi-item checkout against inventory stock. Use the sales list below for history.
      </CardDescription>
    </CardHeader>
    <CardContent class="grid gap-6 lg:grid-cols-2">
      <section class="space-y-3" aria-labelledby="pos-catalog">
        <h3 id="pos-catalog" class="text-sm font-semibold">Catalog</h3>
        <p v-if="loading" class="text-sm text-muted-foreground" role="status">Loading items…</p>
        <ul v-else class="max-h-80 space-y-2 overflow-y-auto">
          <li
            v-for="item in items"
            :key="item.id"
            class="flex items-center justify-between gap-2 rounded-lg border border-border/60 px-3 py-2"
          >
            <div>
              <p class="text-sm font-medium">{{ item.name }}</p>
              <p class="text-xs text-muted-foreground">
                {{ priceOf(item).toFixed(2) }} · stock {{ stockOf(item) }}
              </p>
            </div>
            <Button
              type="button"
              size="sm"
              variant="outline"
              :disabled="stockOf(item) < 1"
              @click="addToCart(item)"
            >
              Add
            </Button>
          </li>
        </ul>
      </section>

      <section class="space-y-3" aria-labelledby="pos-cart">
        <h3 id="pos-cart" class="text-sm font-semibold">Cart</h3>
        <p v-if="!cart.length" class="text-sm text-muted-foreground">No items yet.</p>
        <ul v-else class="space-y-2">
          <li
            v-for="line in cart"
            :key="line.item_id"
            class="flex items-center gap-2 rounded-lg border border-border/60 px-3 py-2"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium">{{ line.name }}</p>
              <p class="text-xs text-muted-foreground">
                {{ line.unit_price.toFixed(2) }} each
              </p>
            </div>
            <Input
              class="w-20"
              type="number"
              min="1"
              :model-value="line.quantity"
              @update:model-value="(v) => updateQty(line.item_id, Number(v))"
            />
            <Button type="button" size="sm" variant="ghost" @click="removeLine(line.item_id)">
              Remove
            </Button>
          </li>
        </ul>

        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-2">
            <Label for="pos-payment">Payment method</Label>
            <select
              id="pos-payment"
              v-model="paymentMethod"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="cash">Cash</option>
              <option value="student_account">Student account</option>
              <option value="upfront">Upfront</option>
            </select>
          </div>
          <div class="space-y-2">
            <Label for="pos-student">Student ID (optional)</Label>
            <Input id="pos-student" v-model="studentId" inputmode="numeric" placeholder="e.g. 12" />
          </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-border/60 pt-3">
          <p class="text-sm font-semibold">Total: {{ total.toFixed(2) }}</p>
          <Button type="button" :disabled="busy || !cart.length" @click="checkout">
            {{ busy ? 'Recording…' : 'Complete sale' }}
          </Button>
        </div>
      </section>
    </CardContent>
  </Card>
</template>
