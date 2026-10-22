import { computed, onMounted, ref } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { teacherPortalApi } from '@/services/api.service'

export interface TeacherClassOption {
  id: number
  name: string
  subjects: string[]
  studentCount: number
  students: Array<{ id: number; name: string; number?: string }>
}

export function useTeacherClassLearners() {
  const { user } = useAuth()
  const loading = ref(true)
  const error = ref<string | null>(null)
  const classes = ref<TeacherClassOption[]>([])
  const selectedClassId = ref<string>('')

  const selectedClass = computed(
    () => classes.value.find((c) => String(c.id) === selectedClassId.value) ?? null,
  )

  const learners = computed(() => selectedClass.value?.students ?? [])

  const allLearners = computed(() => {
    const map = new Map<number, { id: number; name: string; number?: string; className: string }>()
    for (const cls of classes.value) {
      for (const s of cls.students) {
        if (!map.has(s.id)) {
          map.set(s.id, { ...s, className: cls.name })
        }
      }
    }
    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
  })

  async function load() {
    if (!user.value?.teacher_id) {
      loading.value = false
      error.value = 'Teacher profile not linked — open classes from Academics instead.'
      classes.value = []
      return
    }

    loading.value = true
    error.value = null
    try {
      const rows = (await teacherPortalApi.classes()) as Array<{
        class_id: number
        class_name: string
        subjects?: string[]
        student_count?: number
        students?: Array<{ id: number; full_name?: string; student_number?: string }>
      }>
      classes.value = rows.map((c) => ({
        id: c.class_id,
        name: c.class_name,
        subjects: c.subjects ?? [],
        studentCount: c.student_count ?? c.students?.length ?? 0,
        students: (c.students ?? []).map((s) => ({
          id: s.id,
          name: s.full_name || `Student #${s.id}`,
          number: s.student_number,
        })),
      }))
      if (!selectedClassId.value && classes.value.length) {
        selectedClassId.value = String(classes.value[0].id)
      }
    } catch (err) {
      error.value = getErrorMessage(err, 'Failed to load your classes')
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return {
    loading,
    error,
    classes,
    selectedClassId,
    selectedClass,
    learners,
    allLearners,
    reload: load,
  }
}
