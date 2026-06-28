<script setup lang="ts">
import { ref } from 'vue'
import { Download, FileBarChart } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { reportsApi } from '@/services/api.service'

const toast = useToast()
const loadingKey = ref<string | null>(null)

const reports = [
  { key: 'academic', title: 'Academic performance', description: 'Grades and class averages', loader: () => reportsApi.academicPerformance() },
  { key: 'attendance', title: 'Attendance summary', description: 'Presence and absence trends', loader: () => reportsApi.attendance() },
  { key: 'financial', title: 'Financial summary', description: 'Fees, payments, and balances', loader: () => reportsApi.financial() },
]

async function runReport(key: string, loader: () => Promise<unknown>) {
  loadingKey.value = key
  try {
    const data = await loader()
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${key}-report.json`
    link.click()
    URL.revokeObjectURL(url)
    toast.success('Report downloaded')
  } catch (err) {
    toast.error('Report failed', getErrorMessage(err))
  } finally {
    loadingKey.value = null
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Reports</h1>
      <p class="text-muted-foreground">Generate and download school reports</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <Card v-for="report in reports" :key="report.key">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <FileBarChart class="h-5 w-5 text-muted-foreground" aria-hidden="true" />
            {{ report.title }}
          </CardTitle>
          <CardDescription>{{ report.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <Button
            variant="outline"
            :disabled="loadingKey === report.key"
            @click="runReport(report.key, report.loader)"
          >
            <Download class="mr-2 h-4 w-4" aria-hidden="true" />
            {{ loadingKey === report.key ? 'Generating…' : 'Download report' }}
          </Button>
        </CardContent>
      </Card>
    </div>

    <PageLoader v-if="loadingKey" :label="`Generating ${loadingKey} report`" />
  </div>
</template>
