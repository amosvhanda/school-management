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
import { teacherPortalApi, uploadsApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const items = ref<any[]>([])
const saving = ref(false)
const form = ref({ title: '', resource_type: 'notes', topic: '', term: '', description: '', file_url: '', shared: false })

async function load() {
  loading.value = true
  error.value = null
  try {
    items.value = (await teacherPortalApi.resources()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load resources')
  } finally {
    loading.value = false
  }
}

async function onFile(ev: Event) {
  const file = (ev.target as HTMLInputElement).files?.[0]
  if (!file) return
  try {
    const up = await uploadsApi.upload(file, 'resources')
    form.value.file_url = up.url
    toast.success('File uploaded')
  } catch (err) {
    toast.error(getErrorMessage(err))
  }
}

async function save() {
  if (!form.value.title.trim()) {
    toast.warning('Title required')
    return
  }
  saving.value = true
  try {
    await teacherPortalApi.createResource({ ...form.value })
    toast.success('Resource saved')
    form.value = { title: '', resource_type: 'notes', topic: '', term: '', description: '', file_url: '', shared: false }
    await load()
  } catch (err) {
    toast.error(getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Resource library</h2>
      <p class="text-sm text-muted-foreground">Upload notes, PDFs, videos, worksheets, and share by subject/class/topic/term.</p>
    </div>
    <PageLoader v-if="loading" label="Loading resources…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>
      <Card class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">Upload resource</CardTitle>
          <CardDescription>Organize teaching materials for your classes.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
            <div class="space-y-2 sm:col-span-2">
              <Label for="res-title">Title</Label>
              <Input id="res-title" v-model="form.title" required />
            </div>
            <div class="space-y-2">
              <Label for="res-type">Type</Label>
              <select id="res-type" v-model="form.resource_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                <option v-for="t in ['notes','book','pdf','video','image','presentation','worksheet']" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="res-topic">Topic</Label>
              <Input id="res-topic" v-model="form.topic" />
            </div>
            <div class="space-y-2">
              <Label for="res-term">Term</Label>
              <Input id="res-term" v-model="form.term" />
            </div>
            <div class="space-y-2">
              <Label for="res-file">File</Label>
              <Input id="res-file" type="file" @change="onFile" />
            </div>
            <div class="space-y-2 sm:col-span-2">
              <Label for="res-desc">Description</Label>
              <Textarea id="res-desc" v-model="form.description" rows="2" />
            </div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
              <input v-model="form.shared" type="checkbox" class="size-4 rounded border" />
              Share with department
            </label>
            <div class="sm:col-span-2 flex justify-end">
              <Button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save resource' }}</Button>
            </div>
          </form>
        </CardContent>
      </Card>
      <p v-if="!items.length" class="py-6 text-center text-sm text-muted-foreground">No resources yet.</p>
      <Card v-for="r in items" :key="r.id" class="border-border/70">
        <CardContent class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-medium">{{ r.title }}</p>
            <p class="text-sm text-muted-foreground">
              <Badge variant="outline" class="capitalize">{{ r.resource_type }}</Badge>
              <span v-if="r.topic"> · {{ r.topic }}</span>
              <span v-if="r.term"> · {{ r.term }}</span>
              <span v-if="r.shared"> · shared</span>
            </p>
          </div>
          <a v-if="r.file_url" :href="r.file_url" target="_blank" rel="noopener" class="text-sm font-medium text-primary underline-offset-4 hover:underline">Open file</a>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
