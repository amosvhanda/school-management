<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useParentPortalScope } from '@/composables/useParentPortalScope'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { parentPortalApi } from '@/services/index'

interface ConsentForm {
  id: number
  title?: string
  content?: string
  description?: string
  status?: string
  response_status?: string
}

interface ChildOption {
  id: number
  fullName?: string
  full_name?: string
}

const toast = useToast()
const forms = ref<ConsentForm[]>([])
const children = ref<ChildOption[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const respondingId = ref<number | null>(null)
const notes = ref('')
const studentId = ref('')
const saving = ref(false)
const scopeStore = useParentPortalScope('consent-child')

async function load() {
  loading.value = true
  error.value = null
  try {
    const [formRows, childRows] = await Promise.all([
      parentPortalApi.consentForms() as Promise<ConsentForm[]>,
      parentPortalApi.children() as Promise<ChildOption[]>,
    ])
    forms.value = formRows
    children.value = childRows
    studentId.value = scopeStore.resolveChildSelection(childRows, scopeStore.read(''), '')
    if (studentId.value) scopeStore.write(studentId.value)
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load consent forms')
  } finally {
    loading.value = false
  }
}

function startRespond(form: ConsentForm) {
  respondingId.value = form.id
  notes.value = ''
  studentId.value = scopeStore.resolveChildSelection(children.value, scopeStore.read(''), '')
}

async function submitResponse(status: 'approved' | 'declined') {
  if (respondingId.value == null) return
  saving.value = true
  try {
    scopeStore.write(studentId.value)
    await parentPortalApi.respondConsent(respondingId.value, {
      status,
      notes: notes.value.trim() || undefined,
      student_id: studentId.value ? Number(studentId.value) : undefined,
    })
    toast.success(status === 'approved' ? 'Consent approved' : 'Consent declined')
    respondingId.value = null
    await load()
  } catch (err) {
    toast.error('Could not submit response', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)

watch(studentId, (value) => {
  if (!respondingId.value) return
  scopeStore.write(value)
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Consent forms</h1>
      <p class="text-muted-foreground">Review and respond to school consent requests</p>
    </div>

    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <div v-else class="space-y-4">
      <Card v-for="form in forms" :key="form.id">
        <CardHeader>
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
              <CardTitle class="text-lg">{{ form.title ?? 'Consent form' }}</CardTitle>
              <CardDescription v-if="form.content || form.description">
                {{ form.content || form.description }}
              </CardDescription>
            </div>
            <Badge variant="outline" class="capitalize">{{ form.response_status ?? form.status ?? 'pending' }}</Badge>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <template v-if="respondingId === form.id">
            <div v-if="children.length" class="space-y-2">
              <Label for="consent-child">Child</Label>
              <Select v-model="studentId">
                <SelectTrigger id="consent-child">
                  <SelectValue placeholder="Select child" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="c in children" :key="c.id" :value="String(c.id)">
                    {{ c.fullName ?? c.full_name ?? (c.student_number ? `Student ${c.student_number}` : 'Student') }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label for="consent-notes">Notes (optional)</Label>
              <Textarea id="consent-notes" v-model="notes" rows="3" />
            </div>
            <div class="flex flex-wrap gap-2">
              <Button :disabled="saving" @click="submitResponse('approved')">Approve</Button>
              <Button variant="destructive" :disabled="saving" @click="submitResponse('declined')">Decline</Button>
              <Button variant="outline" :disabled="saving" @click="respondingId = null">Cancel</Button>
            </div>
          </template>
          <Button v-else variant="outline" @click="startRespond(form)">Respond</Button>
        </CardContent>
      </Card>
      <p v-if="!forms.length" class="text-sm text-muted-foreground">No consent forms require your response.</p>
    </div>
  </div>
</template>
