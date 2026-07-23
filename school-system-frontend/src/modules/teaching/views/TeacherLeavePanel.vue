<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const data = ref<any>({ balance: {}, requests: [] })
const form = ref({ type: 'annual', start_date: '', end_date: '', reason: '', request_replacement: true })

async function load() {
  loading.value = true
  error.value = null
  try {
    data.value = await teacherPortalApi.leave()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load leave')
  } finally {
    loading.value = false
  }
}

async function apply() {
  try {
    await teacherPortalApi.applyLeave({ ...form.value })
    toast.success('Leave submitted')
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Leave management</h2>
      <p class="text-sm text-muted-foreground">Apply for leave, view balance and status, and optionally request a replacement teacher.</p>
    </div>
    <PageLoader v-if="loading" label="Loading leave…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-3">
        <Card class="border-border/70"><CardContent class="py-4"><p class="text-sm text-muted-foreground">Allowance</p><p class="text-2xl font-semibold">{{ data.balance?.annual_allowance ?? 21 }}</p></CardContent></Card>
        <Card class="border-border/70"><CardContent class="py-4"><p class="text-sm text-muted-foreground">Used</p><p class="text-2xl font-semibold">{{ data.balance?.used ?? 0 }}</p></CardContent></Card>
        <Card class="border-border/70"><CardContent class="py-4"><p class="text-sm text-muted-foreground">Remaining</p><p class="text-2xl font-semibold">{{ data.balance?.remaining ?? 0 }}</p></CardContent></Card>
      </div>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Apply for leave</CardTitle>
          <CardDescription>Approval history appears below after review.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="apply">
            <div class="space-y-2">
              <Label>Type</Label>
              <select v-model="form.type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="annual">Annual</option>
                <option value="sick">Sick</option>
                <option value="maternity">Maternity</option>
                <option value="unpaid">Unpaid</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="space-y-2"><Label>Start</Label><Input v-model="form.start_date" type="date" required /></div>
            <div class="space-y-2"><Label>End</Label><Input v-model="form.end_date" type="date" required /></div>
            <label class="flex items-center gap-2 self-end text-sm"><input v-model="form.request_replacement" type="checkbox" class="size-4" /> Request replacement teacher</label>
            <div class="space-y-2 sm:col-span-2"><Label>Reason</Label><Textarea v-model="form.reason" rows="2" /></div>
            <div class="sm:col-span-2 flex justify-end"><Button type="submit">Submit leave</Button></div>
          </form>
        </CardContent>
      </Card>
      <div class="space-y-2">
        <h3 class="text-sm font-medium text-muted-foreground">Your leave history</h3>
        <Card v-for="r in data.requests || []" :key="r.id" class="border-border/70">
          <CardContent class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
            <span>{{ r.type }} · {{ formatDate(r.start_date) }} → {{ formatDate(r.end_date) }} ({{ r.days }} days)</span>
            <Badge variant="outline" class="capitalize">{{ r.status }}</Badge>
          </CardContent>
        </Card>
        <p v-if="!(data.requests || []).length" class="text-sm text-muted-foreground">No leave requests yet.</p>
      </div>
    </template>
  </div>
</template>
