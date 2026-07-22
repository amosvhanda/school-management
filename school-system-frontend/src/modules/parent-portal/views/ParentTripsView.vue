<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
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
import { formatDate } from '@/lib/format'
import { parentPortalApi } from '@/services/index'

interface TripRow {
  id: number
  name?: string
  destination?: string
  trip_date?: string
  return_date?: string
  fee_amount?: number | string
  currency?: string
  capacity?: number | null
  enrolled_count?: number
  spots_remaining?: number | null
  description?: string
  enrolled_student_ids?: number[]
}

interface ChildOption {
  id: number
  fullName?: string
  full_name?: string
}

const toast = useToast()
const scopeStore = useParentPortalScope('trip-child')
const loading = ref(true)
const error = ref<string | null>(null)
const enrollingId = ref<number | null>(null)
const trips = ref<TripRow[]>([])
const children = ref<ChildOption[]>([])
const studentId = ref('')

const selectedChildId = computed(() => Number(studentId.value || 0))

async function load() {
  loading.value = true
  error.value = null
  try {
    const [tripRows, childRows] = await Promise.all([
      parentPortalApi.trips() as Promise<TripRow[]>,
      parentPortalApi.children() as Promise<ChildOption[]>,
    ])
    trips.value = tripRows
    children.value = childRows
    studentId.value = scopeStore.resolveChildSelection(childRows, scopeStore.read(''), '')
    if (studentId.value) scopeStore.write(studentId.value)
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load school trips')
  } finally {
    loading.value = false
  }
}

watch(studentId, (value) => {
  if (value) scopeStore.write(value)
})

function isEnrolled(trip: TripRow) {
  return (trip.enrolled_student_ids ?? []).includes(selectedChildId.value)
}

function canEnroll(trip: TripRow) {
  if (!selectedChildId.value || isEnrolled(trip)) return false
  if (trip.spots_remaining == null) return true
  return trip.spots_remaining > 0
}

async function enroll(trip: TripRow) {
  if (!studentId.value) {
    toast.error('Select a child', 'Choose which child is going on the trip.')
    return
  }
  enrollingId.value = trip.id
  try {
    await parentPortalApi.enrollTrip(trip.id, { student_id: Number(studentId.value) })
    toast.success('Registered', Number(trip.fee_amount) > 0
      ? 'Trip fee was added to the student account.'
      : 'Your child is registered for this trip.')
    await load()
  } catch (err) {
    toast.error('Registration failed', getErrorMessage(err))
  } finally {
    enrollingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="School trips"
    description="Register your child for upcoming trips. Fees are added to their school account."
    max-width="wide"
  >
    <PageLoader v-if="loading" label="Loading trips" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Register for</CardTitle>
        </CardHeader>
        <CardContent class="max-w-md space-y-2">
          <Label for="trip-child">Child</Label>
          <Select v-model="studentId">
            <SelectTrigger id="trip-child">
              <SelectValue placeholder="Select child" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="child in children" :key="child.id" :value="String(child.id)">
                {{ child.fullName ?? child.full_name ?? `Student #${child.id}` }}
              </SelectItem>
            </SelectContent>
          </Select>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card v-for="trip in trips" :key="trip.id">
          <CardHeader>
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <CardTitle class="text-base">{{ trip.name }}</CardTitle>
                <CardDescription>
                  {{ trip.destination || 'Destination TBC' }}
                  · {{ formatDate(trip.trip_date) }}
                  <span v-if="trip.return_date"> – {{ formatDate(trip.return_date) }}</span>
                </CardDescription>
              </div>
              <Badge v-if="isEnrolled(trip)" variant="default">Registered</Badge>
              <Badge v-else-if="trip.spots_remaining === 0" variant="secondary">Full</Badge>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <p class="text-sm text-muted-foreground">
              {{ trip.description || 'No extra details provided.' }}
            </p>
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-muted-foreground">Fee</dt>
                <dd class="font-semibold tabular-nums">
                  {{ formatMoney(trip.fee_amount ?? 0, String(trip.currency ?? 'USD')) }}
                </dd>
              </div>
              <div>
                <dt class="text-muted-foreground">Spaces</dt>
                <dd class="font-semibold">
                  <template v-if="trip.capacity == null">Open</template>
                  <template v-else>
                    {{ trip.spots_remaining ?? 0 }} of {{ trip.capacity }} left
                  </template>
                </dd>
              </div>
            </dl>
            <Button
              :disabled="!canEnroll(trip) || enrollingId === trip.id"
              @click="enroll(trip)"
            >
              {{
                isEnrolled(trip)
                  ? 'Already registered'
                  : enrollingId === trip.id
                    ? 'Registering…'
                    : 'Register child'
              }}
            </Button>
          </CardContent>
        </Card>
      </div>

      <p v-if="!trips.length" class="text-sm text-muted-foreground">
        No open school trips at the moment.
      </p>
    </template>
  </PageShell>
</template>
