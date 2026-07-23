<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const free = ref<any[]>([])
const exams = ref<any[]>([])
const requests = ref<any[]>([])
const form = ref({ request_type: 'change', details: '', preferred_slot: '' })

async function load() {
  loading.value = true
  error.value = null
  try {
    const [f, e, r] = await Promise.all([
      teacherPortalApi.freePeriods(),
      teacherPortalApi.examTimetable(),
      teacherPortalApi.timetableChangeRequests(),
    ])
    free.value = f as any[]
    exams.value = e as any[]
    requests.value = r as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load timetable tools')
  } finally {
    loading.value = false
  }
}

async function submit() {
  if (!form.value.details.trim()) {
    toast.warning('Details required')
    return
  }
  try {
    await teacherPortalApi.requestTimetableChange({ ...form.value })
    toast.success('Request submitted')
    form.value = { request_type: 'change', details: '', preferred_slot: '' }
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
      <h2 class="text-lg font-semibold">Timetable tools</h2>
      <p class="text-sm text-muted-foreground">View free periods and exam timetable, request changes, or report conflicts. Full personal timetable remains under Academics → My timetable.</p>
    </div>
    <PageLoader v-if="loading" label="Loading…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <div class="grid gap-4 lg:grid-cols-2">
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Free periods (sample week)</CardTitle></CardHeader>
          <CardContent class="max-h-64 space-y-1 overflow-y-auto text-sm">
            <p v-if="!free.length" class="text-muted-foreground">No free slots calculated.</p>
            <p v-for="(s, i) in free.slice(0, 40)" :key="i">{{ s.day }} · {{ s.start_time }}</p>
          </CardContent>
        </Card>
        <Card class="border-border/70">
          <CardHeader><CardTitle class="text-base">Exam timetable</CardTitle></CardHeader>
          <CardContent class="space-y-2 text-sm">
            <p v-if="!exams.length" class="text-muted-foreground">No exams listed.</p>
            <div v-for="e in exams" :key="e.id" class="rounded-lg border border-border/60 px-3 py-2">
              <p class="font-medium">{{ e.name }}</p>
              <p class="text-muted-foreground">{{ formatDate(e.exam_date) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Request change / report conflict</CardTitle>
          <CardDescription>Submitted to administration for review.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
            <div class="space-y-2">
              <Label>Type</Label>
              <select v-model="form.request_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option value="change">Request change</option>
                <option value="conflict">Report conflict</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label>Preferred slot</Label>
              <Input v-model="form.preferred_slot" placeholder="e.g. Monday 10:00" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label>Details</Label>
              <Textarea v-model="form.details" rows="3" required />
            </div>
            <div class="sm:col-span-2 flex justify-end"><Button type="submit">Submit request</Button></div>
          </form>
          <div class="mt-4 space-y-2">
            <p class="text-sm font-medium text-muted-foreground">Your requests</p>
            <div v-for="r in requests" :key="r.id" class="rounded-lg border border-border/60 px-3 py-2 text-sm">
              <Badge variant="outline" class="capitalize">{{ r.status }}</Badge> · {{ r.request_type }} — {{ r.details }}
            </div>
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
