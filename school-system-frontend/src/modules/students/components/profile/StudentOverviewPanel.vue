<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import StudentReadonlyField from '@/modules/students/components/StudentReadonlyField.vue'
import { formatDate } from '@/lib/format'

defineProps<{
  fields: {
    admissionNo?: string | null
    rollNo?: string | null
    classLabel?: string | null
    gradeLevel?: string | null
    house?: string | null
    category?: string | null
    fullName?: string | null
    dateOfBirth?: string | null
    ageLabel?: string | null
    gender?: string | null
    phone?: string | null
    email?: string | null
    address?: string | null
    status?: string | null
    joined?: string | null
    balanceLabel?: string | null
    batch?: string | null
    academicYear?: string | null
    classTeacher?: string | null
  }
  coreSubjects: string[]
  electiveSubjects: string[]
}>()
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Personal information</CardTitle>
        <CardDescription>Identity and contact details on file</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
        <StudentReadonlyField label="Full name" :value="fields.fullName" />
        <StudentReadonlyField label="Date of birth" :value="formatDate(fields.dateOfBirth)" />
        <StudentReadonlyField label="Age" :value="fields.ageLabel ?? '—'" />
        <StudentReadonlyField label="Gender" :value="fields.gender ? String(fields.gender) : '—'" />
        <StudentReadonlyField label="Phone" :value="fields.phone" />
        <StudentReadonlyField label="Email" :value="fields.email" />
        <StudentReadonlyField
          label="Address"
          :value="fields.address || '—'"
          class="sm:col-span-2 lg:col-span-3"
        />
        <StudentReadonlyField label="Status" :value="fields.status" />
        <StudentReadonlyField label="Joined" :value="fields.joined ?? '—'" />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Academic information</CardTitle>
        <CardDescription>Enrollment and placement details</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
        <StudentReadonlyField label="Admission #" :value="fields.admissionNo" />
        <StudentReadonlyField label="Roll #" :value="fields.rollNo" />
        <StudentReadonlyField label="Class / section" :value="fields.classLabel" />
        <StudentReadonlyField label="Grade level" :value="fields.gradeLevel" />
        <StudentReadonlyField label="House" :value="fields.house" />
        <StudentReadonlyField label="Category" :value="fields.category || '—'" />
        <StudentReadonlyField label="Batch" :value="fields.batch || '—'" />
        <StudentReadonlyField label="Academic year" :value="fields.academicYear || '—'" />
        <StudentReadonlyField label="Class teacher" :value="fields.classTeacher || '—'" />
        <StudentReadonlyField label="Balance" :value="fields.balanceLabel" />
      </CardContent>
    </Card>

    <Card>
      <CardHeader class="border-b border-border/50 px-5 py-4">
        <CardTitle class="text-base">Subjects & electives</CardTitle>
        <CardDescription>From the grade subject package and recorded grades</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4 p-5">
        <div>
          <p class="mb-2 text-xs font-medium text-muted-foreground">Core subjects</p>
          <div v-if="coreSubjects.length" class="flex flex-wrap gap-2">
            <span
              v-for="name in coreSubjects"
              :key="`core-${name}`"
              class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary ring-1 ring-primary/15"
            >
              {{ name }}
            </span>
          </div>
          <p v-else class="text-sm text-muted-foreground">No subjects configured for this grade yet.</p>
        </div>
        <div>
          <p class="mb-2 text-xs font-medium text-muted-foreground">Electives</p>
          <div v-if="electiveSubjects.length" class="flex flex-wrap gap-2">
            <span
              v-for="name in electiveSubjects"
              :key="`elective-${name}`"
              class="inline-flex rounded-full border border-border bg-background px-3 py-1 text-xs font-medium text-foreground"
            >
              {{ name }}
            </span>
          </div>
          <p v-else class="text-sm text-muted-foreground">No electives on the subject package.</p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
