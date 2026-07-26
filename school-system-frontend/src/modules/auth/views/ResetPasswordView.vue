<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { ArrowLeft, GraduationCap, Loader2, Lock } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Alert, AlertDescription } from '@/components/ui/alert'
import AuthFormShell from '@/components/auth/AuthFormShell.vue'
import {
  formButtonClass,
  formFieldsAnimateOptions,
  formInputClass,
  formLabelClass,
} from '@/lib/form-standards'
import { cn } from '@/lib/utils'
import { getErrorMessage } from '@/lib/api-response'
import { authApi } from '@/services/api.service'
import { toast } from 'vue-sonner'

const route = useRoute()
const router = useRouter()

const token = computed(() => String(route.query.token ?? ''))
const emailFromQuery = computed(() => String(route.query.email ?? ''))

const schema = toTypedSchema(
  z
    .object({
      email: z.string().email('Enter a valid email address'),
      password: z.string().min(8, 'Password must be at least 8 characters'),
      password_confirmation: z.string().min(8, 'Confirm your password'),
    })
    .refine((v) => v.password === v.password_confirmation, {
      message: 'Passwords do not match',
      path: ['password_confirmation'],
    }),
)

const error = ref<string | null>(null)

const { handleSubmit, isSubmitting } = useForm({
  validationSchema: schema,
  initialValues: {
    email: emailFromQuery.value,
    password: '',
    password_confirmation: '',
  },
})

const onSubmit = handleSubmit(async (values) => {
  error.value = null
  if (!token.value) {
    error.value = 'Reset link is missing or invalid. Request a new password reset.'
    return
  }
  try {
    await authApi.resetPassword({
      email: values.email,
      password: values.password,
      password_confirmation: values.password_confirmation,
      token: token.value,
    })
    toast.success('Password updated', { description: 'You can sign in with your new password.' })
    await router.push({ name: 'login' })
  } catch (err) {
    error.value = getErrorMessage(err, 'Could not reset password. The link may have expired.')
  }
})
</script>

<template>
  <main id="main-content" tabindex="-1" class="flex min-h-svh items-center justify-center p-6 md:p-10">
    <AuthFormShell
      brand-eyebrow="Account recovery"
      brand-title="Choose a strong new password"
      brand-body="Use at least 8 characters. After you save, sign in again with your updated credentials."
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
            <p class="text-[11px] font-semibold tracking-[0.16em] text-primary uppercase">
              School ERP
            </p>
            <h1 class="font-heading text-2xl font-semibold tracking-tight md:text-[1.75rem]">
              Choose a new password
            </h1>
            <p class="text-balance text-sm text-muted-foreground">
              Set a new password for your school account.
            </p>
          </div>
        </div>

        <Alert v-if="!token" variant="destructive" role="alert">
          <AlertDescription>
            This reset link is incomplete. Open the link from your email, or
            <RouterLink
              class="underline underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
              to="/forgot-password"
            >
              request a new one
            </RouterLink>.
          </AlertDescription>
        </Alert>

        <Alert v-else-if="error" variant="destructive" role="alert">
          <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <form
          v-if="token"
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
                <Input
                  v-bind="componentField"
                  type="email"
                  autocomplete="email"
                  :disabled="isSubmitting || !!emailFromQuery"
                  :readonly="!!emailFromQuery"
                  :class="formInputClass"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <FormLabel :class="formLabelClass">New password</FormLabel>
              <FormControl>
                <div class="relative w-full">
                  <Lock
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Input
                    v-bind="componentField"
                    type="password"
                    autocomplete="new-password"
                    :disabled="isSubmitting"
                    :class="cn(formInputClass, 'pl-9')"
                  />
                </div>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <FormField v-slot="{ componentField }" name="password_confirmation">
            <FormItem>
              <FormLabel :class="formLabelClass">Confirm password</FormLabel>
              <FormControl>
                <div class="relative w-full">
                  <Lock
                    class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                  />
                  <Input
                    v-bind="componentField"
                    type="password"
                    autocomplete="new-password"
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
            {{ isSubmitting ? 'Updating…' : 'Update password' }}
          </Button>
        </form>
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
