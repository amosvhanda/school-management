<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Download, Upload } from '@lucide/vue'
import { Button } from '@/components/ui/button'
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
import { getErrorMessage } from '@/lib/api-response'
import { useToast } from '@/composables/useToast'
import { importPeopleCsv, downloadPeopleImportTemplate } from '@/services/import.service'
import type { PeopleImportType } from '@/services/import.service'

const open = defineModel<boolean>('open', { required: true })

const props = defineProps<{
  type: PeopleImportType
  title?: string
}>()

const emit = defineEmits<{ imported: [] }>()

const toast = useToast()
const file = ref<File | null>(null)
const uploading = ref(false)
const downloading = ref(false)
const createLoginUsers = ref(true)
const resultSummary = ref<string | null>(null)
const rowErrors = ref<Array<{ line: number; message: string }>>([])

const typeLabel = computed(() => {
  if (props.type === 'students') return 'students'
  if (props.type === 'teachers') return 'teachers'
  return 'employees'
})

const dialogTitle = computed(() => props.title ?? `Import ${typeLabel.value}`)
const showLoginOption = computed(() => props.type === 'teachers')

watch(open, (isOpen) => {
  if (!isOpen) {
    file.value = null
    resultSummary.value = null
    rowErrors.value = []
    createLoginUsers.value = true
  }
})

function onFileChange(event: Event) {
  const input = event.target as HTMLInputElement
  file.value = input.files?.[0] ?? null
  resultSummary.value = null
  rowErrors.value = []
}

async function downloadTemplate() {
  downloading.value = true
  try {
    await downloadPeopleImportTemplate(props.type)
    toast.success('Template downloaded')
  } catch (err) {
    toast.error('Template download failed', getErrorMessage(err))
  } finally {
    downloading.value = false
  }
}

async function submitImport() {
  if (!file.value) {
    toast.error('Choose a CSV file first')
    return
  }

  uploading.value = true
  resultSummary.value = null
  rowErrors.value = []
  try {
    const result = await importPeopleCsv(props.type, file.value, {
      createLoginUsers: showLoginOption.value && createLoginUsers.value,
    })
    resultSummary.value = `Created ${result.created}, updated ${result.updated}, failed ${result.failed}.`
    if (result.logins_created != null) {
      resultSummary.value += ` Login accounts created: ${result.logins_created} (default password: password123).`
    }
    rowErrors.value = result.errors ?? []
    if (result.failed === 0) {
      toast.success('Import complete', resultSummary.value)
      emit('imported')
      open.value = false
    } else {
      toast.error('Import finished with errors', resultSummary.value)
      if (result.created + result.updated > 0) emit('imported')
    }
  } catch (err) {
    toast.error('Import failed', getErrorMessage(err))
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent class="sm:max-w-lg" aria-describedby="csv-import-desc">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription id="csv-import-desc">
          Download the template, fill in your existing records, then upload the CSV.
          Matching {{ type === 'students' ? 'student numbers' : type === 'teachers' ? 'emails' : 'employee numbers' }}
          update existing rows; new rows are created.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div class="flex flex-wrap gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            :disabled="downloading"
            @click="downloadTemplate"
          >
            <Download class="mr-2 h-4 w-4" aria-hidden="true" />
            {{ downloading ? 'Downloading…' : 'Download template' }}
          </Button>
        </div>

        <div class="space-y-2">
          <Label for="csv-import-file">CSV file</Label>
          <Input
            id="csv-import-file"
            type="file"
            accept=".csv,text/csv"
            :disabled="uploading"
            @change="onFileChange"
          />
          <p v-if="file" class="text-sm text-muted-foreground">Selected: {{ file.name }}</p>
        </div>

        <div v-if="showLoginOption" class="flex items-start gap-2">
          <input
            id="create-login-users"
            v-model="createLoginUsers"
            type="checkbox"
            class="mt-1 size-4 rounded border-input"
          >
          <Label for="create-login-users" class="font-normal leading-snug">
            Create login accounts for teachers (default password
            <code class="text-xs">password123</code>). They should change it after first sign-in.
          </Label>
        </div>

        <div
          v-if="type === 'students'"
          class="rounded-md border border-border bg-muted/30 p-3 text-sm text-muted-foreground"
        >
          Classes must already exist. Use the exact class name in the <code class="text-xs">class</code> column.
        </div>

        <div
          v-if="resultSummary"
          class="rounded-md border border-border bg-muted/40 p-3 text-sm"
          role="status"
          aria-live="polite"
        >
          <p>{{ resultSummary }}</p>
          <ul v-if="rowErrors.length" class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-5 text-destructive">
            <li v-for="err in rowErrors" :key="`${err.line}-${err.message}`">
              Line {{ err.line }}: {{ err.message }}
            </li>
          </ul>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button type="button" variant="outline" :disabled="uploading" @click="open = false">
          Cancel
        </Button>
        <Button type="button" :disabled="uploading || !file" @click="submitImport">
          <Upload class="mr-2 h-4 w-4" aria-hidden="true" />
          {{ uploading ? 'Importing…' : 'Import CSV' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
