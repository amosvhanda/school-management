<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { SlidersHorizontal } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import FormCard from '@/components/forms/FormCard.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useToast } from '@/composables/useToast'
import { useFormApiSubmit } from '@/composables/useFormApiSubmit'
import { useSchoolProfile } from '@/composables/useSchoolProfile'
import { schoolApi } from '@/services/api.service'
import { getErrorMessage } from '@/lib/api-response'
import {
  schoolSettingsFormFields,
  schoolSettingsFormSchema,
} from '@/modules/settings/school-settings-form'

interface SchoolProfile {
  name?: string
  email?: string
  phone?: string
  address?: string
  currency?: string
  currency_locked?: boolean
}

const toast = useToast()
const { loadSchool } = useSchoolProfile()
const loading = ref(true)
const error = ref<string | null>(null)
const currencyLocked = ref(false)
const formResetValues = ref<Record<string, unknown> | undefined>()
const formCardRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const formFields = computed(() =>
  schoolSettingsFormFields({ currencyLocked: currencyLocked.value }),
)

const { submitValues, isSubmitting } = useFormApiSubmit({
  schema: schoolSettingsFormSchema,
  onSubmit: async (values) => {
    await schoolApi.update(values as Record<string, unknown>)
  },
  onSuccess: async () => {
    toast.success('School settings saved')
    await loadSchool({ force: true })
    await load()
  },
  onError: (message, err) => {
    formCardRef.value?.applyServerErrors(err)
    toast.error('Save failed', message)
  },
})

async function load() {
  loading.value = true
  error.value = null
  try {
    const school = await schoolApi.show() as SchoolProfile
    currencyLocked.value = school.currency_locked === true
    formResetValues.value = {
      name: String(school.name ?? ''),
      email: String(school.email ?? ''),
      phone: String(school.phone ?? ''),
      address: String(school.address ?? ''),
      currency: school.currency === 'ZWG' ? 'ZWG' : 'USD',
    }
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load school settings')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="School settings"
    description="Update your school profile and the fees currency used across the system."
    max-width="wide"
  >
    <PageLoader v-if="loading" label="Loading school settings" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />
    <div v-else class="mx-auto max-w-2xl space-y-6">
      <RouterLink
        to="/settings/custom-fields"
        class="block rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
      >
        <Card class="transition-colors hover:bg-muted/40">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
              <SlidersHorizontal class="h-4 w-4" aria-hidden="true" />
              Custom fields
            </CardTitle>
            <CardDescription>Define extra fields for students, staff, and records.</CardDescription>
          </CardHeader>
        </Card>
      </RouterLink>
      <FormCard
        ref="formCardRef"
        title="School profile & fees currency"
        description="Fees currency applies to invoices, fee structures, student accounts, store sales, and trip fees."
        :fields="formFields"
        :schema="schoolSettingsFormSchema"
        :reset-values="formResetValues"
        form-key="school-settings"
        :saving="isSubmitting"
        save-label="Save changes"
        @submit="submitValues"
      />
    </div>
  </PageShell>
</template>
