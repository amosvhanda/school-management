<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  CheckCircle2,
  FilePenLine,
  FileText,
  Filter,
  PenLine,
  Plus,
  RefreshCw,
} from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime, formatRelativeTime } from '@/lib/format'
import { platformApi, type SchoolLicenseRow } from '@/services/api.service'

interface DocumentSignature {
  id?: number
  signer_role?: string | null
  signed_at?: string | null
  signer?: { id?: number; name?: string; email?: string }
}

interface SignableDocument {
  id: number
  title?: string
  document_type?: string
  content?: string
  status?: string
  school_id?: number | null
  created_at?: string | null
  completed_at?: string | null
  school?: { id?: number; name?: string; code?: string } | null
  signatures?: DocumentSignature[]
}

const toast = useToast()
const loading = ref(true)
const error = ref<string | null>(null)
const documents = ref<SignableDocument[]>([])
const schools = ref<SchoolLicenseRow[]>([])
const schoolFilter = ref<string>('all')
const statusFilter = ref<string>('all')
const search = ref('')
const createOpen = ref(false)
const detailOpen = ref(false)
const creating = ref(false)
const signingId = ref<number | null>(null)
const selected = ref<SignableDocument | null>(null)

const createForm = ref({
  school_id: '',
  title: '',
  document_type: 'consent',
  content: '',
})

const documentTypes = [
  { value: 'consent', label: 'Consent form' },
  { value: 'policy', label: 'Policy agreement' },
  { value: 'contract', label: 'Contract' },
  { value: 'admission', label: 'Admission agreement' },
  { value: 'other', label: 'Other' },
]

const pendingCount = computed(() =>
  documents.value.filter((doc) => doc.status === 'pending_signatures').length,
)
const completedCount = computed(() =>
  documents.value.filter((doc) => doc.status === 'completed').length,
)

const filteredDocuments = computed(() => {
  const q = search.value.trim().toLowerCase()
  return documents.value.filter((doc) => {
    if (schoolFilter.value !== 'all' && String(doc.school_id ?? '') !== schoolFilter.value) {
      return false
    }
    if (statusFilter.value !== 'all' && String(doc.status ?? '') !== statusFilter.value) {
      return false
    }
    if (!q) return true
    const haystack = [
      doc.title,
      doc.document_type,
      doc.school?.name,
      doc.school?.code,
      doc.status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    return haystack.includes(q)
  })
})

function statusVariant(status?: string): 'default' | 'secondary' | 'outline' | 'destructive' {
  if (status === 'completed') return 'default'
  if (status === 'pending_signatures') return 'secondary'
  return 'outline'
}

function statusLabel(status?: string) {
  return String(status ?? 'unknown').replace(/_/g, ' ')
}

function typeLabel(type?: string) {
  return documentTypes.find((item) => item.value === type)?.label
    ?? String(type ?? 'Document').replace(/_/g, ' ')
}

async function load() {
  loading.value = true
  error.value = null
  try {
    const [docsPayload, schoolsPayload] = await Promise.all([
      platformApi.documents(),
      platformApi.schoolsOverview().catch(() => null),
    ])
    documents.value = Array.isArray(docsPayload) ? docsPayload as SignableDocument[] : []
    schools.value = Array.isArray(schoolsPayload?.schools) ? schoolsPayload.schools : []
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load signable documents')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  createForm.value = {
    school_id: schoolFilter.value !== 'all' ? schoolFilter.value : (schools.value[0] ? String(schools.value[0].id) : ''),
    title: '',
    document_type: 'consent',
    content: '',
  }
  createOpen.value = true
}

function openDetail(doc: SignableDocument) {
  selected.value = doc
  detailOpen.value = true
}

async function submitCreate() {
  if (!createForm.value.school_id) {
    toast.error('Select a school')
    return
  }
  creating.value = true
  try {
    await platformApi.createDocument({
      school_id: Number(createForm.value.school_id),
      title: createForm.value.title,
      document_type: createForm.value.document_type,
      content: createForm.value.content,
    })
    toast.success('Document created')
    createOpen.value = false
    await load()
  } catch (err) {
    toast.error('Could not create document', getErrorMessage(err))
  } finally {
    creating.value = false
  }
}

async function signDocument(doc: SignableDocument) {
  signingId.value = doc.id
  try {
    await platformApi.signDocument(doc.id, { signer_role: 'super_admin' })
    toast.success('Document signed')
    await load()
    if (selected.value?.id === doc.id) {
      selected.value = documents.value.find((row) => row.id === doc.id) ?? null
    }
  } catch (err) {
    toast.error('Could not sign document', getErrorMessage(err))
  } finally {
    signingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Signable documents"
    description="Optional school-specific e-sign documents. Platform use itself already requires agreement to the School ERP Terms of Use."
    max-width="wide"
  >
    <template #actions>
      <Button variant="outline" :disabled="loading" @click="load">
        <RefreshCw class="size-4" aria-hidden="true" />
        Refresh
      </Button>
      <Button :disabled="!schools.length" @click="openCreate">
        <Plus class="size-4" aria-hidden="true" />
        New document
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading signable documents" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <KpiCard
          title="All documents"
          :value="String(documents.length)"
          subtitle="Across registered schools"
          :icon="FileText"
        />
        <KpiCard
          title="Awaiting signatures"
          :value="String(pendingCount)"
          subtitle="Pending e-sign"
          :icon="FilePenLine"
          :accent="pendingCount > 0 ? 'warning' : undefined"
        />
        <KpiCard
          title="Completed"
          :value="String(completedCount)"
          subtitle="Fully signed"
          :icon="CheckCircle2"
          accent="success"
        />
        <KpiCard
          title="Schools"
          :value="String(schools.length)"
          subtitle="Available for document assignment"
          :icon="Filter"
        />
      </div>

      <Card class="mt-6">
        <CardHeader class="gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <CardTitle class="text-base">Document register</CardTitle>
            <CardDescription>Filter by school and status, then open a document to review or sign</CardDescription>
          </div>
          <div class="grid w-full gap-3 sm:max-w-2xl sm:grid-cols-3">
            <div class="space-y-2">
              <Label for="doc-search">Search</Label>
              <Input
                id="doc-search"
                v-model="search"
                type="search"
                placeholder="Title, school, type…"
              />
            </div>
            <div class="space-y-2">
              <Label for="school-filter">School</Label>
              <Select v-model="schoolFilter">
                <SelectTrigger id="school-filter">
                  <SelectValue placeholder="All schools" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All schools</SelectItem>
                  <SelectItem
                    v-for="school in schools"
                    :key="school.id"
                    :value="String(school.id)"
                  >
                    {{ school.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="status-filter">Status</Label>
              <Select v-model="statusFilter">
                <SelectTrigger id="status-filter">
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All statuses</SelectItem>
                  <SelectItem value="pending_signatures">Awaiting signatures</SelectItem>
                  <SelectItem value="completed">Completed</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="filteredDocuments.length" class="divide-y rounded-lg border">
            <article
              v-for="doc in filteredDocuments"
              :key="doc.id"
              class="flex flex-col gap-3 p-4 lg:flex-row lg:items-start lg:justify-between"
            >
              <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-sm font-medium">{{ doc.title || 'Untitled document' }}</h3>
                  <Badge :variant="statusVariant(doc.status)" class="font-normal capitalize">
                    {{ statusLabel(doc.status) }}
                  </Badge>
                  <Badge variant="outline" class="font-normal">
                    {{ typeLabel(doc.document_type) }}
                  </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ doc.school?.name || doc.school?.code || 'School' }}
                  <span v-if="doc.school?.code"> ({{ doc.school.code }})</span>
                </p>
                <p class="text-xs text-muted-foreground">
                  <span v-if="doc.created_at" :title="formatDateTime(doc.created_at)">
                    Created {{ formatRelativeTime(doc.created_at) }} · {{ formatDateTime(doc.created_at) }}
                  </span>
                  <span v-else>Created —</span>
                  · {{ doc.signatures?.length ?? 0 }} signature{{ (doc.signatures?.length ?? 0) === 1 ? '' : 's' }}
                </p>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" @click="openDetail(doc)">
                  View
                </Button>
                <Button
                  v-if="doc.status !== 'completed'"
                  size="sm"
                  :disabled="signingId === doc.id"
                  @click="signDocument(doc)"
                >
                  <PenLine class="size-4" aria-hidden="true" />
                  {{ signingId === doc.id ? 'Signing…' : 'Sign' }}
                </Button>
              </div>
            </article>
          </div>
          <div
            v-else
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
          >
            <p>No signable documents match these filters.</p>
            <Button class="mt-4" variant="outline" :disabled="!schools.length" @click="openCreate">
              Create the first document
            </Button>
          </div>
        </CardContent>
      </Card>
    </template>

    <Dialog v-model:open="createOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>New signable document</DialogTitle>
          <DialogDescription>
            Assign the document to a school. School staff or you can sign it afterwards.
          </DialogDescription>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCreate">
          <div class="space-y-2">
            <Label for="create-school">School</Label>
            <Select v-model="createForm.school_id">
              <SelectTrigger id="create-school">
                <SelectValue placeholder="Select school" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="school in schools"
                  :key="school.id"
                  :value="String(school.id)"
                >
                  {{ school.name }} ({{ school.code }})
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="create-title">Title</Label>
            <Input id="create-title" v-model="createForm.title" required />
          </div>
          <div class="space-y-2">
            <Label for="create-type">Document type</Label>
            <Select v-model="createForm.document_type">
              <SelectTrigger id="create-type">
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="type in documentTypes"
                  :key="type.value"
                  :value="type.value"
                >
                  {{ type.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label for="create-content">Content</Label>
            <Textarea
              id="create-content"
              v-model="createForm.content"
              required
              rows="8"
              placeholder="Paste or write the agreement text that must be signed…"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" @click="createOpen = false">Cancel</Button>
            <Button type="submit" :disabled="creating">
              {{ creating ? 'Creating…' : 'Create document' }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="detailOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{{ selected?.title || 'Document' }}</DialogTitle>
          <DialogDescription>
            {{ selected?.school?.name || 'School' }}
            · {{ typeLabel(selected?.document_type) }}
          </DialogDescription>
        </DialogHeader>
        <div v-if="selected" class="space-y-4">
          <div class="flex flex-wrap gap-2">
            <Badge :variant="statusVariant(selected.status)" class="font-normal capitalize">
              {{ statusLabel(selected.status) }}
            </Badge>
            <Badge variant="outline" class="font-normal">
              {{ selected.signatures?.length ?? 0 }} signature(s)
            </Badge>
          </div>
          <div class="rounded-lg border bg-muted/30 p-4">
            <p class="whitespace-pre-wrap text-sm">{{ selected.content || 'No content.' }}</p>
          </div>
          <div class="space-y-2">
            <h3 class="text-sm font-medium">Signatures</h3>
            <div v-if="selected.signatures?.length" class="divide-y rounded-lg border">
              <div
                v-for="signature in selected.signatures"
                :key="signature.id ?? `${signature.signer?.id}-${signature.signed_at}`"
                class="px-4 py-3 text-sm"
              >
                <p class="font-medium">{{ signature.signer?.name || 'Signer' }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ signature.signer?.email || '—' }}
                  · {{ signature.signer_role || 'signer' }}
                  ·
                  <span v-if="signature.signed_at" :title="formatDateTime(signature.signed_at)">
                    {{ formatDateTime(signature.signed_at) }}
                  </span>
                  <span v-else>—</span>
                </p>
              </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">No signatures yet.</p>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="detailOpen = false">Close</Button>
            <Button
              v-if="selected.status !== 'completed'"
              :disabled="signingId === selected.id"
              @click="signDocument(selected)"
            >
              <PenLine class="size-4" aria-hidden="true" />
              {{ signingId === selected.id ? 'Signing…' : 'Sign as super admin' }}
            </Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </PageShell>
</template>
