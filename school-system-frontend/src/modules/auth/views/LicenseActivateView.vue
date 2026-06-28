<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import FormCard from '@/components/forms/FormCard.vue'
import { useFormApiSubmit } from '@/composables/useFormApiSubmit'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { activateLicense } from '@/services/auth.service'
import { licenseFormFields, licenseFormSchema } from '@/modules/auth/auth-form'

const router = useRouter()
const { fetchMe } = useAuth()
const toast = useToast()
const formCardRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const { submitValues, isSubmitting } = useFormApiSubmit({
  schema: licenseFormSchema,
  onSubmit: async (values) => {
    await activateLicense(values.licenseKey)
    await fetchMe()
  },
  onSuccess: async () => {
    toast.success('License activated')
    await router.push('/')
  },
  onError: (message, err) => {
    formCardRef.value?.applyServerErrors(err)
    toast.error('Activation failed', message)
  },
})
</script>

<template>
  <main id="main-content" tabindex="-1" class="flex min-h-svh items-center justify-center p-4">
    <div class="w-full max-w-md space-y-2">
      <header class="text-center">
        <h1 class="text-2xl font-semibold tracking-tight">Activate license</h1>
        <p class="text-sm text-muted-foreground">Enter your school license key to restore full access.</p>
      </header>
      <FormCard
        ref="formCardRef"
        title="License key"
        description="Your key was provided when your school subscribed to the platform."
        :fields="licenseFormFields"
        :schema="licenseFormSchema"
        :reset-values="{ licenseKey: '' }"
        form-key="license-activate"
        :saving="isSubmitting"
        save-label="Activate"
        saving-label="Activating…"
        @submit="submitValues"
      />
    </div>
  </main>
</template>
