<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowUpRight, TrendingUp, AlertTriangle, DollarSign, Building2 } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { fetchAnalyticsInsights } from '@/services/dashboard.service'
import { formatMonth } from '@/lib/format'
import { SECTION_HUBS } from '@/lib/section-hubs'

interface ClassPerformance {
  class_name?: string
  average_percent?: number
}

interface StudentAtRisk {
  id: number
  full_name?: string
  student_number?: string
  balance?: number
}

interface FeeTrend {
  month?: string
  total?: number
}

interface AnalyticsData {
  class_performance?: ClassPerformance[]
  students_at_risk?: StudentAtRisk[]
  fee_collection_trend?: FeeTrend[]
  assets_needing_replacement?: number
  top_exam_performance?: Array<{ exam?: { name?: string }; avg_percent?: number }>
}

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<AnalyticsData | null>(null)

const maxFee = computed(() =>
  Math.max(...(data.value?.fee_collection_trend?.map((f) => f.total ?? 0) ?? [1]), 1),
)

const sectionHubs = Object.values(SECTION_HUBS)

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await fetchAnalyticsInsights() as AnalyticsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load analytics'
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">School analytics</h1>
      <p class="text-muted-foreground">Cross-section insights — open a section hub for focused KPIs and shortcuts</p>
    </div>

    <section aria-labelledby="section-hub-links" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <h2 id="section-hub-links" class="sr-only">Section analytics hubs</h2>
      <RouterLink
        v-for="hub in sectionHubs"
        :key="hub.key"
        :to="hub.analyticsPath"
        class="group surface-card flex items-start justify-between gap-2 p-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <div>
          <p class="text-sm font-medium">{{ hub.title }}</p>
          <p class="mt-1 text-xs text-muted-foreground">{{ hub.description }}</p>
        </div>
        <ArrowUpRight class="size-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
      </RouterLink>
    </section>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else-if="data">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="Classes tracked"
          :value="String(data.class_performance?.length ?? 0)"
          subtitle="Performance data"
          :icon="TrendingUp"
        />
        <KpiCard
          title="Students at risk"
          :value="String(data.students_at_risk?.length ?? 0)"
          subtitle="Fees or attendance"
          :icon="AlertTriangle"
          accent="danger"
        />
        <KpiCard
          title="Fee months"
          :value="String(data.fee_collection_trend?.length ?? 0)"
          subtitle="Collection trend"
          :icon="DollarSign"
          accent="success"
        />
        <KpiCard
          title="Assets aging"
          :value="String(data.assets_needing_replacement ?? 0)"
          subtitle="Need replacement"
          :icon="Building2"
          accent="warning"
        />
      </div>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader><CardTitle>Class performance</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div v-for="(cls, i) in data.class_performance ?? []" :key="i" class="space-y-1">
              <div class="flex justify-between text-sm">
                <span>{{ cls.class_name ?? 'Unknown class' }}</span>
                <span class="font-medium">{{ cls.average_percent ?? 0 }}%</span>
              </div>
              <Progress :model-value="cls.average_percent ?? 0" />
            </div>
            <p v-if="!data.class_performance?.length" class="text-sm text-muted-foreground">No performance data.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader><CardTitle>Fee collection trend</CardTitle></CardHeader>
          <CardContent class="space-y-3">
            <div v-for="(point, i) in data.fee_collection_trend ?? []" :key="i" class="space-y-1">
              <div class="flex justify-between text-sm">
                <span>{{ formatMonth(point.month) }}</span>
                <span>${{ Number(point.total ?? 0).toLocaleString() }}</span>
              </div>
              <Progress :model-value="((point.total ?? 0) / maxFee) * 100" />
            </div>
            <p v-if="!data.fee_collection_trend?.length" class="text-sm text-muted-foreground">No fee data.</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Students at risk</CardTitle></CardHeader>
        <CardContent>
          <Table v-if="data.students_at_risk?.length">
            <TableHeader>
              <TableRow>
                <TableHead>Student</TableHead>
                <TableHead>Number</TableHead>
                <TableHead>Balance</TableHead>
                <TableHead></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="s in data.students_at_risk" :key="s.id">
                <TableCell>{{ s.full_name ?? '—' }}</TableCell>
                <TableCell>{{ s.student_number ?? '—' }}</TableCell>
                <TableCell>${{ Number(s.balance ?? 0).toLocaleString() }}</TableCell>
                <TableCell>
                  <RouterLink :to="`/students/${s.id}`" class="text-sm text-primary hover:underline">View</RouterLink>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-else class="text-sm text-muted-foreground">No at-risk students identified.</p>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
