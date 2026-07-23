<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getStoredToken, baseURL } from '@/lib/api'
import { teacherPortalApi } from '@/services/api.service'

const classId = ref('')
const busy = ref(false)

async function download(type: string, format: 'csv' | 'print') {
  busy.value = true
  try {
    const params: Record<string, string | number> = { type, format }
    if (classId.value) params.class_id = Number(classId.value)
    const path = teacherPortalApi.exportUrl(params)
    const res = await fetch(`${baseURL}${path}`, {
      headers: { Authorization: `Bearer ${getStoredToken() ?? ''}`, Accept: format === 'csv' ? 'text/csv' : 'text/html' },
    })
    const blob = await res.blob()
    const url = URL.createObjectURL(blob)
    if (format === 'print') {
      window.open(url, '_blank')
    } else {
      const a = document.createElement('a')
      a.href = url
      a.download = `${type}.csv`
      a.click()
    }
    URL.revokeObjectURL(url)
  } finally {
    busy.value = false
  }
}

const exports = [
  { type: 'class_list', label: 'Class lists' },
  { type: 'attendance', label: 'Attendance registers' },
  { type: 'marksheet', label: 'Mark sheets' },
  { type: 'assignments', label: 'Assignment reports' },
  { type: 'exams', label: 'Exam reports' },
  { type: 'progress', label: 'Progress reports' },
  { type: 'lesson_plans', label: 'Lesson plans' },
]
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold">Printing &amp; export</h2>
      <p class="text-sm text-muted-foreground">Generate CSV downloads or printable HTML (Print to PDF). Excel can be imported from CSV.</p>
    </div>
    <Card class="border-border/70">
      <CardHeader>
        <CardTitle class="text-base">Export options</CardTitle>
        <CardDescription>Optionally filter by class ID for class-scoped exports.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="max-w-xs space-y-2">
          <Label for="exp-class">Class ID (optional)</Label>
          <Input id="exp-class" v-model="classId" type="number" />
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
          <div v-for="item in exports" :key="item.type" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-3">
            <span class="text-sm font-medium">{{ item.label }}</span>
            <div class="flex gap-2">
              <Button size="sm" variant="outline" :disabled="busy" @click="download(item.type, 'csv')">CSV</Button>
              <Button size="sm" :disabled="busy" @click="download(item.type, 'print')">Print</Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
