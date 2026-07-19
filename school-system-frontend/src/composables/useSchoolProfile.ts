import { computed, onMounted, ref } from 'vue'
import { schoolApi } from '@/services/api.service'
import type { SchoolCurrency } from '@/lib/finance-constants'

export interface SchoolProfile {
  id?: number
  name?: string
  code?: string
  email?: string
  phone?: string
  address?: string
  currency?: string
  currency_locked?: boolean
}

const school = ref<SchoolProfile | null>(null)
const loading = ref(false)
let loaded = false

export function useSchoolProfile() {
  async function loadSchool(options?: { force?: boolean }) {
    if (!options?.force && loaded && school.value) return school.value
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

  const currency = computed<SchoolCurrency>(() =>
    school.value?.currency === 'ZWG' ? 'ZWG' : 'USD',
  )

  return { school, loading, loadSchool, currency }
}
