import { defineStore } from 'pinia'
import { ref } from 'vue'
import { schoolApi } from '@/services/api.service'

export interface SchoolConfig {
  terminology?: Record<string, string>
  settings?: Record<string, unknown>
  custom_fields?: Array<Record<string, unknown>>
  school?: Record<string, unknown> | null
}

export const useConfigStore = defineStore('config', () => {
  const terminology = ref<Record<string, string>>({})
  const settings = ref<Record<string, unknown>>({})
  const customFields = ref<Array<Record<string, unknown>>>([])
  const school = ref<Record<string, unknown> | null>(null)
  const loaded = ref(false)

  async function fetchPublicConfig() {
    const payload = await schoolApi.publicConfig() as SchoolConfig
    terminology.value = payload.terminology ?? {}
    settings.value = payload.settings ?? {}
    customFields.value = payload.custom_fields ?? []
    school.value = payload.school ?? null
    loaded.value = true
    return payload
  }

  function term(key: string, fallback: string) {
    return terminology.value[key] ?? fallback
  }

  function markLoadedWithoutSchool() {
    terminology.value = {}
    settings.value = {}
    customFields.value = []
    school.value = null
    loaded.value = true
  }

  function reset() {
    terminology.value = {}
    settings.value = {}
    customFields.value = []
    school.value = null
    loaded.value = false
  }

  return {
    terminology,
    settings,
    customFields,
    school,
    loaded,
    fetchPublicConfig,
    markLoadedWithoutSchool,
    term,
    reset,
  }
})
