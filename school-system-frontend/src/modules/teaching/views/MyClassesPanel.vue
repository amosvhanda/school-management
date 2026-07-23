<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

const loading = ref(true)
const error = ref<string | null>(null)
const classes = ref<any[]>([])
const search = ref('')
const selectedStudent = ref<any | null>(null)
const detailLoading = ref(false)

async function load() {
  loading.value = true
  error.value = null
  try {
    classes.value = (await teacherPortalApi.classes()) as any[]
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load classes')
  } finally {
    loading.value = false
  }
}

function filteredStudents(cls: any) {
  const q = search.value.trim().toLowerCase()
  const students = cls.students ?? []
  if (!q) return students
  return students.filter((s: any) =>
    String(s.full_name ?? '').toLowerCase().includes(q) ||
    String(s.student_number ?? '').toLowerCase().includes(q),
  )
}

async function openStudent(id: number) {
  detailLoading.value = true
  try {
    selectedStudent.value = await teacherPortalApi.student(id)
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not load student'))
  } finally {
    detailLoading.value = false
  }
}

onMounted(load)

</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold text-foreground">My Classes</h2>
      <p class="text-sm text-muted-foreground">View assigned classes, search students, and open learner history.</p>
    </div>
    <PageLoader v-if="loading" label="Loading…" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <template v-else>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="grow space-y-2">
          <Label for="student-search">Search students</Label>
          <Input id="student-search" v-model="search" placeholder="Name or student number" />
        </div>
      </div>

      <p v-if="!classes.length" class="py-8 text-center text-sm text-muted-foreground">No classes assigned yet.</p>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card v-for="cls in classes" :key="cls.class_id" class="border-border/70">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">{{ cls.class_name }}</CardTitle>
            <CardDescription>
              {{ (cls.subjects || []).join(', ') || 'No subject' }} · {{ cls.student_count }} students
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-2">
            <button
              v-for="s in filteredStudents(cls)"
              :key="s.id"
              type="button"
              class="flex w-full items-center justify-between rounded-lg border border-border/60 px-3 py-2 text-left text-sm hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              @click="openStudent(s.id)"
            >
              <span class="font-medium">{{ s.full_name }}</span>
              <span class="text-muted-foreground">{{ s.student_number }}</span>
            </button>
            <p v-if="!filteredStudents(cls).length" class="text-sm text-muted-foreground">No matching students.</p>
          </CardContent>
        </Card>
      </div>

      <Card v-if="selectedStudent" class="border-border/70">
        <CardHeader>
          <CardTitle class="text-base">{{ selectedStudent.student?.full_name }}</CardTitle>
          <CardDescription>Academic, attendance, and behaviour history</CardDescription>
        </CardHeader>
        <CardContent v-if="detailLoading">
          <p class="text-sm text-muted-foreground">Loading detail…</p>
        </CardContent>
        <CardContent v-else class="grid gap-4 md:grid-cols-3">
          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Attendance</p>
            <ul class="space-y-1 text-sm">
              <li v-for="a in selectedStudent.attendance_history || []" :key="a.id">
                {{ a.date }} — <Badge variant="outline" class="capitalize">{{ a.status }}</Badge>
              </li>
              <li v-if="!(selectedStudent.attendance_history || []).length" class="text-muted-foreground">No records.</li>
            </ul>
          </div>
          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Behaviour</p>
            <ul class="space-y-1 text-sm">
              <li v-for="b in selectedStudent.behaviour_history || []" :key="b.id">
                {{ b.recorded_on }} · {{ b.points }} pts · {{ b.category }}
              </li>
              <li v-if="!(selectedStudent.behaviour_history || []).length" class="text-muted-foreground">No records.</li>
            </ul>
          </div>
          <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Discipline</p>
            <ul class="space-y-1 text-sm">
              <li v-for="d in selectedStudent.discipline_history || []" :key="d.id">
                {{ d.incident_date }} · {{ d.category }}
              </li>
              <li v-if="!(selectedStudent.discipline_history || []).length" class="text-muted-foreground">No records.</li>
            </ul>
            <div v-if="selectedStudent.parent_contacts" class="mt-4 space-y-1 text-sm">
              <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Parent contact</p>
              <p>{{ selectedStudent.parent_contacts.phone || '—' }}</p>
              <p>{{ selectedStudent.parent_contacts.email || '—' }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

    </template>
  </div>
</template>
