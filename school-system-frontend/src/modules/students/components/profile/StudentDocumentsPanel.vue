<script setup lang="ts">
import { computed } from 'vue'
import {
  CalendarDays,
  Download,
  Eye,
  FileArchive,
  FileImage,
  FileText,
  HardDrive,
  Plus,
  Trash2,
  Upload,
} from '@lucide/vue'
import EmptyState from '@/components/feedback/EmptyState.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'
import { formatDate } from '@/lib/format'
import { STATUS_TONE_BADGE, STATUS_TONE_ICON } from '@/lib/ui-status'

export type ProfileDocument = {
  id: number
  name?: string
  type?: string | null
  mime_type?: string | null
  size?: number | null
  url?: string | null
  created_at?: string | null
}

const props = defineProps<{
  documents: ProfileDocument[]
  loading?: boolean
  error?: string | null
  canManage?: boolean
  uploading?: boolean
  deletingId?: number | null
  documentType: string
  typeOptions: Array<{ label: string; value: string }>
}>()

const emit = defineEmits<{
  retry: []
  upload: []
  remove: [doc: ProfileDocument]
  'update:documentType': [value: string]
}>()

const countLabel = computed(() => {
  const n = props.documents.length
  return n === 1 ? '1 file' : `${n} files`
})

function formatFileSize(bytes?: number | null) {
  if (!bytes || bytes < 1) return '—'
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function extLabel(doc: ProfileDocument) {
  const name = String(doc.name ?? '')
  const fromName = name.includes('.') ? name.split('.').pop()?.toUpperCase() : null
  if (fromName && fromName.length <= 5) return fromName
  const mime = String(doc.mime_type ?? '').toLowerCase()
  if (mime.includes('pdf')) return 'PDF'
  if (mime.includes('jpeg') || mime.includes('jpg')) return 'JPG'
  if (mime.includes('png')) return 'PNG'
  if (mime.includes('zip')) return 'ZIP'
  if (mime.includes('word')) return 'DOC'
  return 'FILE'
}

function iconFor(doc: ProfileDocument) {
  const ext = extLabel(doc)
  if (ext === 'JPG' || ext === 'PNG' || ext === 'WEBP' || ext === 'GIF') return FileImage
  if (ext === 'ZIP') return FileArchive
  return FileText
}

function toneFor(_doc: ProfileDocument, index: number) {
  const tones = [
    STATUS_TONE_ICON.accent,
    STATUS_TONE_ICON.info,
    STATUS_TONE_ICON.success,
    STATUS_TONE_ICON.danger,
    STATUS_TONE_ICON.warning,
    STATUS_TONE_ICON.neutral,
  ] as const
  return tones[index % tones.length]!
}

function badgeTone(_doc: ProfileDocument, index: number) {
  const tones = [
    STATUS_TONE_BADGE.accent,
    STATUS_TONE_BADGE.info,
    STATUS_TONE_BADGE.success,
    STATUS_TONE_BADGE.danger,
    STATUS_TONE_BADGE.warning,
    STATUS_TONE_BADGE.neutral,
  ] as const
  return tones[index % tones.length]!
}

async function downloadAll() {
  const withUrl = props.documents.filter((d) => d.url)
  for (const doc of withUrl) {
    window.open(String(doc.url), '_blank', 'noopener,noreferrer')
  }
}
</script>

<template>
  <Card>
    <CardHeader class="flex flex-col gap-4 space-y-0 border-b border-border/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="space-y-1">
        <CardTitle class="flex items-center gap-2 text-base">
          <FileText class="size-4 text-primary" aria-hidden="true" />
          Documents
          <Badge variant="secondary" class="font-normal">{{ countLabel }}</Badge>
        </CardTitle>
      </div>
      <div class="flex flex-wrap items-end gap-2 print:hidden">
        <div v-if="canManage" class="space-y-1">
          <label for="profile-document-type" class="text-xs font-medium text-muted-foreground">Type</label>
          <select
            id="profile-document-type"
            :value="documentType"
            class="flex h-9 min-w-[10rem] rounded-xl border border-input bg-background px-3 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            @change="emit('update:documentType', ($event.target as HTMLSelectElement).value)"
          >
            <option v-for="option in typeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
        <Button
          v-if="canManage"
          type="button"
          variant="outline"
          size="sm"
          class="rounded-xl"
          :disabled="uploading"
          :aria-busy="uploading"
          @click="emit('upload')"
        >
          <Upload class="mr-1.5 size-4" aria-hidden="true" />
          {{ uploading ? 'Uploading…' : 'Upload' }}
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          class="rounded-xl"
          :disabled="!documents.some((d) => d.url)"
          @click="downloadAll"
        >
          <Download class="mr-1.5 size-4" aria-hidden="true" />
          Download all
        </Button>
      </div>
    </CardHeader>

    <CardContent class="p-5">
      <PageLoader v-if="loading" label="Loading documents" />
      <ErrorState v-else-if="error" :description="error" @retry="emit('retry')" />
      <div
        v-else-if="documents.length"
        class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
      >
        <article
          v-for="(doc, index) in documents"
          :key="doc.id"
          class="flex flex-col gap-4 rounded-2xl border border-border/70 bg-card p-4 shadow-[var(--shadow-soft)]"
        >
          <div class="flex items-start justify-between gap-3">
            <div
              :class="cn('flex size-12 items-center justify-center rounded-2xl', toneFor(doc, index))"
              aria-hidden="true"
            >
              <component :is="iconFor(doc)" class="size-5" />
            </div>
            <span
              :class="cn('rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide', badgeTone(doc, index))"
            >
              {{ extLabel(doc) }}
            </span>
          </div>

          <div class="min-w-0 space-y-1">
            <h3 class="truncate text-sm font-semibold text-foreground">
              {{ doc.name || (doc.type ? String(doc.type).replaceAll('_', ' ') : 'Document') }}
            </h3>
            <p class="text-xs capitalize text-muted-foreground">
              {{ (doc.type ?? 'other').replaceAll('_', ' ') }}
            </p>
            <div class="flex flex-wrap gap-3 pt-1 text-xs text-muted-foreground">
              <span class="inline-flex items-center gap-1">
                <HardDrive class="size-3.5" aria-hidden="true" />
                {{ formatFileSize(doc.size) }}
              </span>
              <span class="inline-flex items-center gap-1">
                <CalendarDays class="size-3.5" aria-hidden="true" />
                {{ formatDate(doc.created_at) }}
              </span>
            </div>
          </div>

          <div class="mt-auto flex items-center justify-between gap-2 border-t border-border/50 pt-3">
            <Badge variant="success" class="gap-1 font-normal">
              On file
            </Badge>
            <div class="flex gap-1.5">
              <Button
                v-if="doc.url"
                type="button"
                variant="outline"
                size="icon-sm"
                class="rounded-xl"
                as-child
              >
                <a :href="doc.url" target="_blank" rel="noopener noreferrer" :aria-label="`View ${doc.name}`">
                  <Eye class="size-4" />
                </a>
              </Button>
              <Button
                v-if="doc.url"
                type="button"
                variant="outline"
                size="icon-sm"
                class="rounded-xl"
                as-child
              >
                <a :href="doc.url" target="_blank" rel="noopener noreferrer" download :aria-label="`Download ${doc.name}`">
                  <Download class="size-4" />
                </a>
              </Button>
              <Button
                v-if="canManage"
                type="button"
                variant="outline"
                size="icon-sm"
                class="rounded-xl"
                :disabled="deletingId === doc.id"
                :aria-label="`Delete ${doc.name ?? 'document'}`"
                @click="emit('remove', doc)"
              >
                <Trash2 class="size-4" />
              </Button>
            </div>
          </div>
        </article>
      </div>
      <EmptyState
        v-else
        title="No documents yet"
        description="Upload birth certificates, IDs, medical notes, and other student files."
      >
        <Button v-if="canManage" type="button" size="sm" class="rounded-xl" @click="emit('upload')">
          <Plus class="mr-1.5 size-4" aria-hidden="true" />
          Upload document
        </Button>
      </EmptyState>
    </CardContent>
  </Card>
</template>
