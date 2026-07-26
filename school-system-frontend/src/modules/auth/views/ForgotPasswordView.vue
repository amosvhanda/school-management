<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { ArrowLeft, GraduationCap, Loader2, Mail } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Alert, AlertDescription } from '@/components/ui/alert'
import AuthFormShell from '@/components/auth/AuthFormShell.vue'
import { brandName, brandTagline } from '@/lib/brand'
import {
  formButtonClass,
  formFieldsAnimateOptions,
  formInputClass,
  formLabelClass,
} from '@/lib/form-standards'
import { cn } from '@/lib/utils'
import { getErrorMessage } from '@/lib/api-response'
import { authApi } from '@/services/api.service'

const schema = toTypedSchema(
  z.object({
    email: z.string().email('Enter a valid email address'),
  }),
)

const sent = ref(false)
const error = ref<string | null>(null)

const { handleSubmit, isSubmitting } = useForm({
  validationSchema: schema,
  initialValues: { email: '' },
})

const onSubmit = handleSubmit(async (values) => {
  error.value = null
  try {
    await authApi.forgotPassword({ email: values.email })
    sent.value = true
  } catch (err) {
    error.value = getErrorMessage(err, 'Could not send reset link. Try again.')
  }
})
</script>

<template>
  <main id="main-content" tabindex="-1" class="flex min-h-svh items-center justify-center p-6 md:p-10">
    <AuthFormShell
      brand-eyebrow="Account recovery"
      brand-title="Get back into your school account"
      brand-body="We’ll email a secure link so you can choose a new password and keep teaching, learning, or managing uninterrupted."
    >
      <div class="space-y-6">
        <Button variant="ghost" size="sm" class="-ml-2 w-fit" as-child>
          <RouterLink
            to="/login"
            class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <ArrowLeft class="mr-2 size-4" aria-hidden="true" />
            Back to sign in
          </RouterLink>
        </Button>

        <div class="flex flex-col items-center gap-3 text-center md:items-start md:text-left">
          <div
            class="flex size-11 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            aria-hidden="true"
          >
            <GraduationCap class="size-5" />
          </div>
          <div class="space-y-1.5">
            <p class="font-heading text-lg font-semibold tracking-tight text-primary">
              {{ brandName }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ brandTagline }}
            </p>
            <h1 class="font-heading text-2xl font-semibold tracking-tight md:text-[1.75rem]">
              Reset your password
            </h1>
            <p class="text-balance text-sm text-muted-foreground">
              Enter the email for your school account. If it exists, we’ll send a reset link.
            </p>
          </div>
        </div>

        <Alert v-if="sent" class="border-chart-2/30 bg-chart-2/10" role="status">
          <AlertDescription>
            If an account exists for that email, a password reset link has been sent. Check your inbox
            (and spam folder).
          </AlertDescription>
        </Alert>

        <Alert v-else-if="error" variant="destructive" role="alert">
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <form
          v-if="!sent"
          v-auto-animate="formFieldsAnimateOptions"
          class="space-y-4"
          :aria-busy="isSubmitting"
          novalidate
          @submit="onSubmit"
        >
          <FormField v-slot="{ componentField }" name="email">
            <FormItem>
              <FormLabel :class="formLabelClass">Email</FormLabel>
              <FormControl>
                <div class="relative w-full">
                  <Mail
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Input
                    v-bind="componentField"
                    type="email"
                    inputmode="email"
                    autocomplete="email"
                    placeholder="you@school.co.zw"
                    :disabled="isSubmitting"
                    :class="cn(formInputClass, 'pl-9')"
                  />
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <Button
            type="submit"
            :class="cn(formButtonClass, 'w-full')"
            :disabled="isSubmitting"
            :aria-busy="isSubmitting"
          >
            <Loader2 v-if="isSubmitting" class="mr-2 size-4 animate-spin" aria-hidden="true" />
            {{ isSubmitting ? 'Sending…' : 'Send reset link' }}
          </Button>
        </form>

        <p v-if="sent" class="text-sm text-muted-foreground">
          <RouterLink
            to="/login"
            class="font-medium text-foreground underline underline-offset-4 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            Return to sign in
          </RouterLink>
        </p>
      </div>

      <template #below>
        <footer class="px-2 text-center text-xs text-muted-foreground">
          By continuing, you agree to our
          <RouterLink
            to="/legal/terms"
            class="underline underline-offset-4 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            Terms of Service
          </RouterLink>
          and
          <RouterLink
            to="/legal/privacy"
            class="underline underline-offset-4 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            Privacy Policy
          </RouterLink>.
        </footer>
      </template>
    </AuthFormShell>
  </main>
</template>
