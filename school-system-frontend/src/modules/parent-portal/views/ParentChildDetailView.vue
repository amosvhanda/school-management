<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { parentPortalApi } from '@/services/index'
import { formatMoney } from '@/lib/finance-constants'

const route = useRoute()
const studentId = computed(() => String(route.params.id))

const loading = ref(true)
const error = ref<string | null>(null)
const child = ref<Record<string, unknown> | null>(null)
const results = ref<Record<string, unknown>[]>([])
const attendance = ref<Record<string, unknown>[]>([])
const fees = ref<Record<string, unknown> | null>(null)
const discipline = ref<Record<string, unknown>[]>([])
const progress = ref<Record<string, unknown> | null>(null)

const childName = computed(() =>
  String(child.value?.full_name ?? child.value?.fullName ?? `Student #${studentId.value}`),
)

async function load() {
  loading.value = true
  error.value = null
  try {
    const children = await parentPortalApi.children() as Record<string, unknown>[]
    child.value = children.find((c) => String(c.id) === studentId.value) ?? null

    const [resultsData, attendanceData, feesData, disciplineData, progressData] = await Promise.all([
      parentPortalApi.results(studentId.value).catch(() => []),
      parentPortalApi.attendance(studentId.value).catch(() => []),
      parentPortalApi.fees(studentId.value).catch(() => null),
      parentPortalApi.discipline(studentId.value).catch(() => []),
      parentPortalApi.progress(studentId.value).catch(() => null),
    ])

    results.value = resultsData as Record<string, unknown>[]
    attendance.value = attendanceData as Record<string, unknown>[]
    fees.value = feesData as Record<string, unknown> | null
    discipline.value = disciplineData as Record<string, unknown>[]
    progress.value = progressData as Record<string, unknown> | null
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load child details'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">{{ childName }}</h1>
        <p class="text-muted-foreground">
          {{ child?.class ? `Class: ${child.class}` : 'Child profile' }}
        </p>
      </div>
      <Button variant="outline" as-child>
        <RouterLink to="/portal/children">Back to children</RouterLink>
      </Button>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Balance</CardDescription>
            <CardTitle class="text-xl tabular-nums">
              {{ formatMoney(child?.balance, String(child?.currency ?? 'USD')) }}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card v-if="progress">
          <CardHeader class="pb-2">
            <CardDescription>Overall average</CardDescription>
            <CardTitle class="text-xl">{{ progress.overall_average ?? progress.average ?? '—' }}</CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Recent absences</CardDescription>
            <CardTitle class="text-xl">{{ attendance.filter((a) => a.status === 'absent').length }}</CardTitle>
          </CardHeader>
        </Card>
      </div>

      <Tabs default-value="results">
        <TabsList class="grid w-full grid-cols-2 sm:grid-cols-4">
          <TabsTrigger value="results">Results</TabsTrigger>
          <TabsTrigger value="attendance">Attendance</TabsTrigger>
          <TabsTrigger value="fees">Fees</TabsTrigger>
          <TabsTrigger value="discipline">Discipline</TabsTrigger>
        </TabsList>

        <TabsContent value="results" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Academic results</CardTitle></CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="results.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Subject</TableHead>
                    <TableHead>Score</TableHead>
                    <TableHead>Grade</TableHead>
                    <TableHead>Term</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(row, i) in results" :key="i">
                    <TableCell>{{ row.subject ?? '—' }}</TableCell>
                    <TableCell>{{ row.score ?? '—' }}{{ row.total ? ` / ${row.total}` : '' }}</TableCell>
                    <TableCell><Badge variant="outline">{{ row.grade ?? '—' }}</Badge></TableCell>
                    <TableCell>{{ row.term ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No results published yet.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="attendance" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Attendance history</CardTitle></CardHeader>
            <CardContent class="overflow-x-auto p-0">
              <Table v-if="attendance.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Date</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Notes</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(row, i) in attendance" :key="i">
                    <TableCell>{{ row.date ?? row.attendance_date ?? '—' }}</TableCell>
                    <TableCell class="capitalize">{{ row.status ?? '—' }}</TableCell>
                    <TableCell>{{ row.notes ?? row.reason ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="p-6 text-sm text-muted-foreground">No attendance records.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="fees" class="mt-4">
          <Card>
            <CardHeader>
              <CardTitle>Fees & invoices</CardTitle>
              <CardDescription v-if="fees?.balance != null">
                Outstanding: {{ formatMoney(fees.balance, String(fees.currency ?? child?.currency ?? 'USD')) }}
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <Table v-if="Array.isArray(fees?.invoices) && fees.invoices.length">
                <TableHeader>
                  <TableRow>
                    <TableHead>Invoice</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Balance</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="inv in (fees.invoices as Record<string, unknown>[])" :key="String(inv.id)">
                    <TableCell>{{ inv.invoice_number ?? inv.description ?? '—' }}</TableCell>
                    <TableCell>{{ formatMoney(inv.amount, String(inv.currency ?? 'USD')) }}</TableCell>
                    <TableCell>{{ formatMoney(inv.balance, String(inv.currency ?? 'USD')) }}</TableCell>
                    <TableCell class="capitalize">{{ inv.status ?? '—' }}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
              <p v-else class="text-sm text-muted-foreground">No invoice records.</p>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="discipline" class="mt-4">
          <Card>
            <CardHeader><CardTitle>Discipline records</CardTitle></CardHeader>
            <CardContent>
              <ul v-if="discipline.length" class="space-y-3">
                <li v-for="(row, i) in discipline" :key="i" class="rounded-lg border p-4">
                  <p class="font-medium capitalize">{{ row.incident_type ?? row.type ?? 'Incident' }}</p>
                  <p class="text-sm text-muted-foreground">{{ row.incident_date ?? row.date ?? '—' }} · {{ row.severity ?? '—' }}</p>
                  <p v-if="row.description" class="mt-2 text-sm">{{ row.description }}</p>
                </li>
              </ul>
              <p v-else class="text-sm text-muted-foreground">No discipline records.</p>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </template>
  </div>
</template>
