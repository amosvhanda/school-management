<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate, formatDateTime } from '@/lib/format'
import { academicStructureApi, operationsApi, platformApi, studentsApi } from '@/services/api.service'
import { fetchList, moduleEndpoints } from '@/services'

const props = defineProps<{
  studentId: string | number
}>()

const emit = defineEmits<{ refreshed: [] }>()

const toast = useToast()
const loading = ref(true)
const saving = ref(false)
const lifecycle = ref<Record<string, unknown> | null>(null)

const classes = ref<Array<Record<string, unknown>>>([])
const streams = ref<Array<Record<string, unknown>>>([])
const houses = ref<Array<Record<string, unknown>>>([])
const routes = ref<Array<Record<string, unknown>>>([])

const placeClassId = ref('')
const placeStreamId = ref('none')
const placeHouseId = ref('none')
const placeYear = ref(String(new Date().getFullYear()))
const placeReason = ref('mid_year_transfer')

const action = ref('suspend')
const actionReason = ref('')
const academicYear = ref(String(new Date().getFullYear()))
const nextAcademicYear = ref(String(new Date().getFullYear() + 1))

const medicalNotes = ref('')
const medicalAllergies = ref('')
const medicalEmergency = ref('')
const medicalEmergencyPhone = ref('')

const transportRouteId = ref('')
const libraryBookId = ref('')
const books = ref<Array<Record<string, unknown>>>([])
const counsellingSummary = ref('')

const placement = computed(() => lifecycle.value?.placement as Record<string, unknown> | undefined)
const admission = computed(() => lifecycle.value?.admission as Record<string, unknown> | undefined)
const enrollments = computed(() => (admission.value?.enrollments as Array<Record<string, unknown>>) ?? [])
const statusEvents = computed(() => (lifecycle.value?.status_events as Array<Record<string, unknown>>) ?? [])
const subjectPackage = computed(() => (placement.value?.subject_package as Array<Record<string, unknown>>) ?? [])
const counselling = computed(() => (lifecycle.value?.counselling as Array<Record<string, unknown>>) ?? [])
const medical = computed(() => lifecycle.value?.medical as Record<string, unknown> | null)
const transport = computed(() => (lifecycle.value?.transport as Array<Record<string, unknown>>) ?? [])
const hostel = computed(() => lifecycle.value?.hostel as Record<string, unknown> | null)
const homeroom = computed(() => {
  const t = placement.value?.homeroom_teacher as Record<string, unknown> | undefined
  if (!t) return '—'
  return String(t.full_name ?? [t.first_name, t.last_name].filter(Boolean).join(' ') ?? '—')
})

function triggerBlobDownload(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

async function load() {
  loading.value = true
  try {
    const [life, classRows, streamRows, houseRows, routeRows, bookRows] = await Promise.all([
      studentsApi.lifecycle(props.studentId),
      fetchList(moduleEndpoints.classes, { all: true }).catch(() => []),
      academicStructureApi.streams.list({ all: true }).catch(() => []),
      academicStructureApi.houses.list({ all: true }).catch(() => []),
      fetchList(moduleEndpoints.transportRoutes, { all: true }).catch(() => []),
      fetchList(moduleEndpoints.libraryBooks, { all: true }).catch(() => []),
    ])
    const lifeData = life as Record<string, unknown>
    lifecycle.value = lifeData
    classes.value = classRows as Array<Record<string, unknown>>
    streams.value = streamRows as Array<Record<string, unknown>>
    houses.value = houseRows as Array<Record<string, unknown>>
    routes.value = routeRows as Array<Record<string, unknown>>
    books.value = bookRows as Array<Record<string, unknown>>

    const student = lifeData.student as Record<string, unknown> | undefined
    placeClassId.value = student?.class_id != null ? String(student.class_id) : ''
    placeStreamId.value = student?.stream_id != null ? String(student.stream_id) : 'none'
    placeHouseId.value = student?.house_id != null ? String(student.house_id) : 'none'

    const med = lifeData.medical as Record<string, unknown> | null
    medicalNotes.value = String(med?.conditions ?? med?.medical_conditions ?? med?.notes ?? '')
    medicalAllergies.value = String(med?.allergies ?? '')
    medicalEmergency.value = String(med?.emergency_notes ?? med?.emergency_contact ?? '')
    medicalEmergencyPhone.value = String(med?.medications ?? '')
  } catch (err) {
    toast.error('Could not load journey', getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function savePlacement() {
  if (!placeClassId.value) {
    toast.error('Class required', 'Select a class for placement.')
    return
  }
  saving.value = true
  try {
    await studentsApi.place(props.studentId, {
      class_id: Number(placeClassId.value),
      stream_id: placeStreamId.value !== 'none' ? Number(placeStreamId.value) : null,
      house_id: placeHouseId.value !== 'none' ? Number(placeHouseId.value) : null,
      academic_year: placeYear.value,
      reason: placeReason.value || 'mid_year_transfer',
      apply_fees: false,
    })
    toast.success('Placement updated')
    await load()
    emit('refreshed')
  } catch (err) {
    toast.error('Placement failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function runTransition() {
  saving.value = true
  try {
    await studentsApi.lifecycleTransition(props.studentId, {
      action: action.value,
      reason: actionReason.value || undefined,
      issue_tc: action.value === 'transfer_out' || action.value === 'withdraw',
      deactivate_account: !['reinstate', 'activate'].includes(action.value),
    })
    toast.success('Lifecycle action completed')
    await load()
    emit('refreshed')
  } catch (err) {
    toast.error('Action failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function runPromote() {
  saving.value = true
  try {
    await studentsApi.lifecyclePromote(props.studentId, {
      academic_year: academicYear.value,
      next_academic_year: nextAcademicYear.value,
    })
    toast.success('Promotion decision applied')
    await load()
    emit('refreshed')
  } catch (err) {
    toast.error('Promotion failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function downloadTc() {
  try {
    const blob = await studentsApi.downloadTransferCertificate(props.studentId)
    triggerBlobDownload(blob, `student_${props.studentId}_transfer_certificate.html`)
    toast.success('Transfer certificate downloaded')
    await load()
  } catch (err) {
    toast.error('Download failed', getErrorMessage(err))
  }
}

async function downloadTranscript() {
  try {
    const blob = await studentsApi.downloadTranscript(props.studentId)
    triggerBlobDownload(blob, `student_${props.studentId}_transcript.html`)
    toast.success('Transcript downloaded')
  } catch (err) {
    toast.error('Download failed', getErrorMessage(err))
  }
}

async function saveMedical() {
  saving.value = true
  try {
    await operationsApi.health.updateProfile(props.studentId, {
      conditions: medicalNotes.value,
      allergies: medicalAllergies.value,
      emergency_notes: medicalEmergency.value,
      medications: medicalEmergencyPhone.value,
    })
    toast.success('Medical profile saved')
    await load()
  } catch (err) {
    toast.error('Medical save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function allocateTransport() {
  if (!transportRouteId.value) return
  saving.value = true
  try {
    await operationsApi.transport.allocateStudent({
      student_id: Number(props.studentId),
      route_id: Number(transportRouteId.value),
      status: 'active',
      allocated_at: new Date().toISOString().slice(0, 10),
    })
    toast.success('Transport allocated')
    await load()
  } catch (err) {
    toast.error('Transport allocation failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function borrowBook() {
  if (!libraryBookId.value) return
  saving.value = true
  try {
    const due = new Date()
    due.setDate(due.getDate() + 14)
    await operationsApi.library.borrow({
      student_id: Number(props.studentId),
      book_id: Number(libraryBookId.value),
      due_at: due.toISOString().slice(0, 10),
    })
    toast.success('Library book loaned')
    libraryBookId.value = ''
  } catch (err) {
    toast.error('Library loan failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

async function addCounselling() {
  if (!counsellingSummary.value.trim()) return
  saving.value = true
  try {
    await platformApi.storeIntervention({
      student_id: Number(props.studentId),
      intervention_type: 'counselling',
      status: 'open',
      summary: counsellingSummary.value.trim(),
    })
    toast.success('Counselling note recorded')
    counsellingSummary.value = ''
    await load()
  } catch (err) {
    toast.error('Counselling save failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

watch(() => props.studentId, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <p v-if="loading" class="text-sm text-muted-foreground">Loading student journey…</p>

    <template v-else-if="lifecycle">
      <div class="grid gap-4 lg:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Current class</CardDescription>
            <CardTitle class="text-lg">
              {{ (placement?.class as { name?: string } | undefined)?.name ?? 'Unassigned' }}
            </CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            Homeroom: {{ homeroom }}
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Stream / House</CardDescription>
            <CardTitle class="text-lg">
              {{ (placement?.stream as { name?: string } | undefined)?.name ?? '—' }}
              /
              {{ (placement?.house as { name?: string } | undefined)?.name ?? '—' }}
            </CardTitle>
          </CardHeader>
        </Card>
        <Card>
          <CardHeader class="pb-2">
            <CardDescription>Status</CardDescription>
            <CardTitle class="text-lg capitalize">{{ admission?.status ?? '—' }}</CardTitle>
          </CardHeader>
          <CardContent class="text-sm text-muted-foreground">
            {{ admission?.status_reason || 'No status reason on file' }}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Change placement</CardTitle>
          <CardDescription>Move class, stream, or house mid-year without losing history</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label>Class</Label>
            <Select v-model="placeClassId">
              <SelectTrigger><SelectValue placeholder="Select class" /></SelectTrigger>
              <SelectContent>
                <SelectItem v-for="c in classes" :key="String(c.id)" :value="String(c.id)">{{ c.name }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label>Stream</Label>
            <Select v-model="placeStreamId">
              <SelectTrigger><SelectValue placeholder="Optional" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                <SelectItem v-for="s in streams" :key="String(s.id)" :value="String(s.id)">{{ s.name }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label>House</Label>
            <Select v-model="placeHouseId">
              <SelectTrigger><SelectValue placeholder="Optional" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">None</SelectItem>
                <SelectItem v-for="h in houses" :key="String(h.id)" :value="String(h.id)">{{ h.name }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label>Academic year</Label>
            <Input v-model="placeYear" />
          </div>
          <div class="space-y-2 sm:col-span-2">
            <Label>Reason</Label>
            <Input v-model="placeReason" placeholder="e.g. stream change, capacity balancing" />
          </div>
          <div class="sm:col-span-2">
            <Button :disabled="saving" @click="savePlacement">Save placement</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Year-end promote / repeat</CardTitle>
          <CardDescription>Uses pass mark rules to promote, repeat, or graduate</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-3">
          <div class="space-y-2">
            <Label>Current year</Label>
            <Input v-model="academicYear" />
          </div>
          <div class="space-y-2">
            <Label>Next year</Label>
            <Input v-model="nextAcademicYear" />
          </div>
          <div class="flex items-end">
            <Button :disabled="saving" @click="runPromote">Apply decision</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status actions</CardTitle>
          <CardDescription>Suspend, withdraw, transfer, graduate, and sync accounts</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <Label>Action</Label>
            <Select v-model="action">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="suspend">Suspend</SelectItem>
                <SelectItem value="reinstate">Reinstate</SelectItem>
                <SelectItem value="withdraw">Withdraw</SelectItem>
                <SelectItem value="expel">Expel</SelectItem>
                <SelectItem value="transfer_out">Transfer out</SelectItem>
                <SelectItem value="graduate">Graduate → alumni</SelectItem>
                <SelectItem value="deactivate">Deactivate account</SelectItem>
                <SelectItem value="activate">Activate account</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label>Reason</Label>
            <Input v-model="actionReason" placeholder="Required for audit trail" />
          </div>
          <div class="flex flex-wrap gap-2 sm:col-span-2">
            <Button :disabled="saving" @click="runTransition">Run action</Button>
            <Button variant="outline" :disabled="saving" @click="downloadTc">Download TC</Button>
            <Button variant="outline" :disabled="saving" @click="downloadTranscript">Download transcript</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Enrollment history</CardTitle>
        </CardHeader>
        <CardContent class="overflow-x-auto p-0">
          <Table v-if="enrollments.length">
            <TableHeader>
              <TableRow>
                <TableHead>Class</TableHead>
                <TableHead>Stream</TableHead>
                <TableHead>House</TableHead>
                <TableHead>Year</TableHead>
                <TableHead>From</TableHead>
                <TableHead>To</TableHead>
                <TableHead>Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="row in enrollments" :key="String(row.id)">
                <TableCell>
                  {{
                    (row.class_model as { name?: string } | undefined)?.name
                      ?? (row.classModel as { name?: string } | undefined)?.name
                      ?? '—'
                  }}
                </TableCell>
                <TableCell>{{ (row.stream as { name?: string } | undefined)?.name ?? '—' }}</TableCell>
                <TableCell>{{ (row.house as { name?: string } | undefined)?.name ?? '—' }}</TableCell>
                <TableCell>{{ row.academic_year ?? '—' }}</TableCell>
                <TableCell>{{ formatDate(row.enrolled_at) }}</TableCell>
                <TableCell>{{ formatDate(row.left_at, '—') }}</TableCell>
                <TableCell class="capitalize">{{ row.status ?? '—' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-else class="p-6 text-sm text-muted-foreground">No enrollment history yet.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Subject package</CardTitle>
          <CardDescription>Subjects for this grade and stream</CardDescription>
        </CardHeader>
        <CardContent>
          <ul v-if="subjectPackage.length" class="space-y-2 text-sm">
            <li v-for="item in subjectPackage" :key="String(item.id)" class="flex items-center gap-2">
              <Badge variant="outline">{{ item.is_core ? 'Core' : 'Elective' }}</Badge>
              <span>{{ item.subject ?? '—' }}</span>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">
            No subject package configured yet. Add items under Academics → Subject packages.
          </p>
        </CardContent>
      </Card>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Medical & emergency</CardTitle>
          </CardHeader>
          <CardContent class="grid gap-3">
            <div class="space-y-2">
              <Label>Conditions / notes</Label>
              <Textarea v-model="medicalNotes" rows="2" />
            </div>
            <div class="space-y-2">
              <Label>Allergies</Label>
              <Input v-model="medicalAllergies" />
            </div>
            <div class="space-y-2">
              <Label>Emergency notes</Label>
              <Input v-model="medicalEmergency" />
            </div>
            <div class="space-y-2">
              <Label>Medications</Label>
              <Input v-model="medicalEmergencyPhone" />
            </div>
            <Button :disabled="saving" @click="saveMedical">Save medical profile</Button>
            <p v-if="medical" class="text-xs text-muted-foreground">Profile on file.</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Transport & hostel</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label>Allocate transport route</Label>
              <div class="flex gap-2">
                <Select v-model="transportRouteId">
                  <SelectTrigger class="flex-1"><SelectValue placeholder="Select route" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="r in routes" :key="String(r.id)" :value="String(r.id)">
                      {{ r.name ?? 'Transport route' }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Button :disabled="saving || !transportRouteId" @click="allocateTransport">Allocate</Button>
              </div>
            </div>
            <ul v-if="transport.length" class="space-y-1 text-sm">
              <li v-for="t in transport" :key="String(t.id)" class="capitalize">
                {{ (t.route as { name?: string } | undefined)?.name ?? 'Route' }} · {{ t.status }}
              </li>
            </ul>
            <p class="text-sm text-muted-foreground">
              Hostel:
              <span v-if="hostel">
                {{ ((hostel.bed as { room?: { hostel?: { name?: string } } })?.room?.hostel?.name) ?? 'Allocated' }}
              </span>
              <span v-else>Not allocated — use Operations → Hostels to assign a bed</span>
            </p>
            <div class="space-y-2">
              <Label>Loan library book</Label>
              <div class="flex gap-2">
                <Select v-model="libraryBookId">
                  <SelectTrigger class="flex-1"><SelectValue placeholder="Select book" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="b in books" :key="String(b.id)" :value="String(b.id)">
                      {{ b.title ?? 'Library book' }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Button :disabled="saving || !libraryBookId" @click="borrowBook">Loan</Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Counselling / interventions</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>New note</Label>
            <Textarea v-model="counsellingSummary" rows="2" placeholder="Counselling summary…" />
            <Button :disabled="saving || !counsellingSummary.trim()" @click="addCounselling">Add note</Button>
          </div>
          <ul v-if="counselling.length" class="space-y-2 text-sm">
            <li v-for="row in counselling" :key="String(row.id)" class="rounded-lg border p-3">
              <p class="font-medium capitalize">{{ row.intervention_type ?? 'counselling' }} · {{ row.status }}</p>
              <p>{{ row.summary }}</p>
              <p class="text-xs text-muted-foreground">{{ formatDateTime(row.created_at) }}</p>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">No counselling notes yet.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status audit trail</CardTitle>
        </CardHeader>
        <CardContent class="overflow-x-auto p-0">
          <Table v-if="statusEvents.length">
            <TableHeader>
              <TableRow>
                <TableHead>When</TableHead>
                <TableHead>Action</TableHead>
                <TableHead>From → To</TableHead>
                <TableHead>By</TableHead>
                <TableHead>Reason</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="ev in statusEvents" :key="String(ev.id)">
                <TableCell>{{ formatDateTime(ev.created_at) }}</TableCell>
                <TableCell class="capitalize">{{ ev.action }}</TableCell>
                <TableCell class="capitalize">{{ ev.from_status ?? '—' }} → {{ ev.to_status }}</TableCell>
                <TableCell>{{ (ev.performer as { name?: string } | undefined)?.name ?? '—' }}</TableCell>
                <TableCell>{{ ev.reason ?? '—' }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p v-else class="p-6 text-sm text-muted-foreground">No lifecycle status events yet.</p>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
