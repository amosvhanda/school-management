<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import {
  Download,
  Eye,
  FileText,
  FolderOpen,
  Plus,
  RefreshCw,
  Upload,
} from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { resolveMediaUrl } from '@/lib/media-url'
import { cn } from '@/lib/utils'
import { STATUS_TONE_BADGE } from '@/lib/ui-status'
import { studentsApi } from '@/services/api.service'

type DocRow = {
  id: number
  name?: string
  type?: string | null
  size?: number | null
  url?: string | null
  created_at?: string | null
  verified_at?: string | null
  expires_at?: string | null
  is_favourite?: boolean
  student?: {
    id?: number
    full_name?: string
    student_number?: string
    class?: string
    photo_url?: string | null
  }
  student_id?: number
}

const CATEGORIES = [
  'birth_certificate',
  'transfer_certificate',
  'mark_sheet',
  'identity',
  'address_proof',
  'passport',
  'medical',
  'admission',
  'other',
]

const toast = useToast()
const loading = ref(false)
const uploading = ref(false)
const search = ref('')
const smartFilter = ref<'all' | 'recent' | 'favourites' | 'verified' | 'pending' | 'expired'>('all')
const typeFilter = ref('')
const rows = ref<DocRow[]>([])
const counts = ref<Record<string, number>>({})
const students = ref<
  Array<{
    id: number
    full_name?: string
    first_name?: string
    last_name?: string
    photo_url?: string | null
  }>
>([])
const uploadStudentId = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const expiringNote = computed(() => {
  const expired = Number(counts.value.expired ?? 0)
  if (expired < 1) return null
  return `${expired} document(s) have expired. Review them in the Expired filter.`
})

function formatSize(bytes?: number | null) {
  if (!bytes || bytes < 1) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function studentInitials(name: string) {
  return name.split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'ST'
}

function studentPhoto(doc: DocRow) {
  const raw = String(doc.student?.photo_url ?? '').trim()
  return resolveMediaUrl(raw || null)
}

function statusOf(doc: DocRow) {
  if (doc.expires_at && new Date(doc.expires_at) < new Date()) return { label: 'Expired', tone: STATUS_TONE_BADGE.danger }
  if (doc.verified_at) return { label: 'Verified', tone: STATUS_TONE_BADGE.success }
  return { label: 'Pending', tone: STATUS_TONE_BADGE.warning }
}

async function load() {
  loading.value = true
  try {
    const params: Record<string, string | number | boolean | undefined> = {
      search: search.value.trim() || undefined,
      type: typeFilter.value || undefined,
      per_page: 48,
    }
    if (smartFilter.value === 'recent') params.recent = true
    if (smartFilter.value === 'favourites') params.favourite = true
    if (smartFilter.value === 'verified') params.verified = true
    if (smartFilter.value === 'pending') params.pending = true
    if (smartFilter.value === 'expired') params.expired = true

    const res = await studentsApi.documentsIndex(params as never)
    rows.value = (res.data ?? []).map((d) => ({
      ...d,
      url: resolveMediaUrl(String(d.url ?? '')) ?? (d.url as string | null),
    })) as DocRow[]
    counts.value = res.counts ?? {}
  } catch (err) {
    rows.value = []
    toast.error('Could not load documents', getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function loadStudents() {
  try {
    students.value = (await studentsApi.list({
      per_page: 200,
      include: 'classModel',
    } as never)) as typeof students.value
  } catch {
    students.value = []
  }
}

function pickUpload() {
  if (!uploadStudentId.value) {
    toast.warning('Select a student', 'Choose which student the files belong to.')
    return
  }
  fileInput.value?.click()
}

async function onFiles(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (!files.length || !uploadStudentId.value) return
  uploading.value = true
  try {
    await studentsApi.uploadDocuments(uploadStudentId.value, files, typeFilter.value || 'other')
    toast.success('Documents uploaded')
    await load()
  } catch (err) {
    toast.error('Upload failed', getErrorMessage(err))
  } finally {
    uploading.value = false
    input.value = ''
  }
}

watch([smartFilter, typeFilter], load)
onMounted(async () => {
  await loadStudents()
  await load()
})
</script>

<template>
  <PageShell
    title="Document vault"
    description="Secure store of student admission and compliance documents."
    :breadcrumbs="['Students', 'Document vault']"
    max-width="wide"
  >
    <template #actions>
      <Button type="button" variant="outline" class="rounded-xl" @click="load">
        <RefreshCw class="mr-1.5 size-4" />
        Refresh
      </Button>
      <Button type="button" class="rounded-xl" :disabled="uploading" @click="pickUpload">
        <Plus class="mr-1.5 size-4" />
        {{ uploading ? 'Uploading…' : 'Upload Documents' }}
      </Button>
    </template>

    <input ref="fileInput" type="file" multiple class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onFiles" />

    <div
      v-if="expiringNote"
      class="mb-4 rounded-2xl border border-chart-3/30 bg-chart-3/10 px-4 py-3 text-sm"
    >
      {{ expiringNote }}
    </div>

    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <aside class="space-y-4">
        <Card>
          <CardContent class="space-y-3 p-4">
            <Label>Upload for student</Label>
            <Select v-model="uploadStudentId">
              <SelectTrigger class="rounded-xl"><SelectValue placeholder="Select student" /></SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="s in students"
                  :key="s.id"
                  :value="String(s.id)"
                >
                  {{ s.full_name || [s.first_name, s.last_name].filter(Boolean).join(' ') || `#${s.id}` }}
                </SelectItem>
              </SelectContent>
            </Select>
            <Button type="button" class="w-full rounded-xl" @click="pickUpload">
              <Upload class="mr-1.5 size-4" />
              Upload
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2"><CardTitle class="text-sm">Smart filters</CardTitle></CardHeader>
          <CardContent class="space-y-1 p-2 pt-0">
            <button
              v-for="item in [
                { id: 'all', label: 'All Documents', count: counts.all },
                { id: 'recent', label: 'Recent', count: counts.recent },
                { id: 'favourites', label: 'Favourites', count: counts.favourites },
                { id: 'verified', label: 'Verified', count: counts.verified },
                { id: 'pending', label: 'Pending', count: counts.pending },
                { id: 'expired', label: 'Expired', count: counts.expired },
              ] as const"
              :key="item.id"
              type="button"
              :class="cn(
                'flex w-full items-center justify-between rounded-xl px-3 py-2 text-sm',
                smartFilter === item.id ? 'bg-primary/10 font-semibold text-primary' : 'hover:bg-muted/50',
              )"
              @click="smartFilter = item.id"
            >
              <span>{{ item.label }}</span>
              <span class="tabular-nums text-muted-foreground">{{ item.count ?? 0 }}</span>
            </button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2"><CardTitle class="text-sm">Categories</CardTitle></CardHeader>
          <CardContent class="space-y-1 p-2 pt-0">
            <button
              type="button"
              :class="cn('flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm', !typeFilter && 'bg-muted/60')"
              @click="typeFilter = ''"
            >
              <FolderOpen class="size-3.5" /> All types
            </button>
            <button
              v-for="cat in CATEGORIES"
              :key="cat"
              type="button"
              :class="cn(
                'flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm capitalize',
                typeFilter === cat && 'bg-primary/10 text-primary',
              )"
              @click="typeFilter = cat"
            >
              <FolderOpen class="size-3.5 shrink-0" />
              {{ cat.replaceAll('_', ' ') }}
            </button>
          </CardContent>
        </Card>
      </aside>

      <section class="space-y-4">
        <div class="flex flex-wrap gap-2">
          <Input
            v-model="search"
            placeholder="Search documents, student…"
            class="max-w-sm rounded-xl"
            @keyup.enter="load"
          />
          <Button type="button" variant="outline" class="rounded-xl" @click="load">Search</Button>
        </div>

        <PageLoader v-if="loading" label="Loading documents" />
        <div v-else-if="rows.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="doc in rows"
            :key="doc.id"
            class="flex flex-col gap-3 rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
          >
            <div class="flex items-start justify-between gap-2">
              <div :class="cn('flex size-12 items-center justify-center rounded-2xl', STATUS_TONE_BADGE.info)">
                <FileText class="size-5" />
              </div>
              <span :class="cn('rounded-full border px-2 py-0.5 text-[10px] font-semibold', statusOf(doc).tone)">
                {{ statusOf(doc).label }}
              </span>
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold">{{ doc.name || 'Document' }}</p>
              <Badge variant="secondary" class="mt-1 capitalize font-normal">
                {{ (doc.type ?? 'other').replaceAll('_', ' ') }}
              </Badge>
              <div class="mt-2 flex min-w-0 items-center gap-2">
                <Avatar class="size-8 shrink-0">
                  <AvatarImage
                    v-if="studentPhoto(doc)"
                    :src="studentPhoto(doc)!"
                    :alt="`${doc.student?.full_name ?? 'Student'} photo`"
                  />
                  <AvatarFallback class="bg-primary/10 text-[10px] font-semibold text-primary">
                    {{ studentInitials(String(doc.student?.full_name ?? 'Student')) }}
                  </AvatarFallback>
                </Avatar>
                <p class="min-w-0 truncate text-xs text-muted-foreground">
                  {{ doc.student?.full_name ?? 'Student' }}
                  <span v-if="doc.student?.student_number"> · {{ doc.student.student_number }}</span>
                  <span v-if="doc.student?.class"> · {{ doc.student.class }}</span>
                </p>
              </div>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ formatSize(doc.size) }} · {{ formatDate(doc.created_at) }}
              </p>
            </div>
            <div class="mt-auto flex gap-1.5 border-t border-border/50 pt-3">
              <Button v-if="doc.url" type="button" variant="outline" size="icon-sm" class="rounded-xl" as-child>
                <a :href="doc.url" target="_blank" rel="noopener noreferrer" :aria-label="`View ${doc.name}`">
                  <Eye class="size-4" />
                </a>
              </Button>
              <Button v-if="doc.url" type="button" variant="outline" size="icon-sm" class="rounded-xl" as-child>
                <a :href="doc.url" target="_blank" rel="noopener noreferrer" download :aria-label="`Download ${doc.name}`">
                  <Download class="size-4" />
                </a>
              </Button>
            </div>
          </article>
        </div>
        <EmptyState v-else title="No documents" description="Upload student files to build the vault." />
      </section>
    </div>
  </PageShell>
</template>
