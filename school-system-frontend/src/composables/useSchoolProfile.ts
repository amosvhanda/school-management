import { onMounted, ref } from 'vue'
import { schoolApi } from '@/services/api.service'

interface SchoolProfile {
  name?: string
  code?: string
  email?: string
}

const school = ref<SchoolProfile | null>(null)
const loading = ref(false)
let loaded = false

export function useSchoolProfile() {
  async function loadSchool() {
    if (loaded && school.value) return school.value
    loading.value = true
    try {
      school.value = await schoolApi.show() as SchoolProfile
      loaded = true
      return school.value
    } catch {
      school.value = null
      return null
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    void loadSchool()
  })

  return { school, loading, loadSchool }
}
