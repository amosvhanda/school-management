<script setup lang="ts">
import { computed, ref, type HTMLAttributes } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Loader2, Lock, Mail } from '@lucide/vue'
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useFormApiSubmit } from '@/composables/useFormApiSubmit'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { resolvePostLoginRedirect } from '@/app/router/guards'
import { isStaffDashboardRole } from '@/lib/permissions'
import { DEMO_ACCOUNTS, WEB_DEMO_ACCOUNTS, type DemoAccount } from '@/lib/demo-accounts'
import {
  formButtonClass,
  formFieldsAnimateOptions,
  formInputClass,
  formLabelClass,
  formSurfaceClass,
} from '@/lib/form-standards'
import { loginFormSchema } from '@/modules/auth/auth-form'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const router = useRouter()
const route = useRoute()
const { login, logout } = useAuth()
const toast = useToast()
const isDev = computed(() => import.meta.env.DEV)
const quickLoginRole = ref<string | null>(null)

const { submit, isSubmitting, setValues } = useFormApiSubmit({
  schema: loginFormSchema,
  initialValues: { email: '', password: '' },
  onSubmit: async (values) => {
    await completeLogin(values.email, values.password)
  },
  onSuccess: () => {
    toast.success('Signed in successfully')
  },
  onError: (message) => {
    toast.error('Login failed', message)
  },
})

async function completeLogin(email: string, password: string) {
  const result = await login(email, password)

  if (
    !isStaffDashboardRole(result.user.role)
    && result.user.role !== 'parent'
    && result.user.role !== 'student'
    && result.user.role !== 'super_admin'
  ) {
    await logout()
    throw new Error('Your account role cannot access this application.')
  }

  if (result.user.platform_terms_accepted === false) {
    await router.push({ name: 'platform-terms-accept' })
    return
  }

  const redirect = resolvePostLoginRedirect(
    result.user,
    typeof route.query.redirect === 'string' ? route.query.redirect : null,
    (path) => router.resolve(path),
  )

  await router.push(redirect)
}

function fillAccount(account: DemoAccount) {
  setValues({ email: account.email, password: account.password })
}

async function quickSignIn(account: DemoAccount) {
  if (!account.webAccess) {
    toast.error('Not available', 'Student web portal is not enabled. Use another profile to test the app.')
    return
  }

  quickLoginRole.value = account.role
  try {
    fillAccount(account)
    await completeLogin(account.email, account.password)
    toast.success(`Signed in as ${account.label}`)
  } catch (err) {
    toast.error('Login failed', err instanceof Error ? err.message : 'Could not sign in')
  } finally {
    quickLoginRole.value = null
  }
}
</script>

<template>
  <main id="main-content" tabindex="-1" class="flex min-h-svh items-center justify-center p-6 md:p-10">
    <div :class="cn('flex w-full max-w-sm flex-col gap-6 md:max-w-4xl', props.class)">

      <!-- Top Section: Test Profiles (Only in Dev Mode) -->
      <Card v-if="isDev" :class="formSurfaceClass">
        <CardHeader class="space-y-1 pb-3">
          <CardTitle class="text-base">Test profiles</CardTitle>
          <CardDescription>
            One-click sign-in for each role. Run
            <code class="rounded bg-muted px-1 py-0.5 font-mono text-[11px]">php artisan db:seed --class=UserSeeder</code>
            if an account is missing.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <Button
              v-for="account in DEMO_ACCOUNTS"
              :key="account.email"
              type="button"
              variant="outline"
              size="sm"
              class="h-auto flex-col items-start gap-0.5 px-2 py-2 text-left whitespace-normal"
              :disabled="isSubmitting || !!quickLoginRole"
              :aria-busy="quickLoginRole === account.role"
              @click="quickSignIn(account)"
            >
              <span class="text-xs font-medium">{{ account.label }}</span>
              <span class="text-[10px] font-normal text-muted-foreground">{{ account.destination }}</span>
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Main Login Section split into Form and Visual Image Banner -->
      <Card class="overflow-hidden p-0" :class="formSurfaceClass">
        <CardContent class="grid p-0 md:grid-cols-2">

          <!-- Column 1: Form -->
          <form
            v-auto-animate="formFieldsAnimateOptions"
            class="space-y-6 p-6 md:p-8"
            :aria-busy="isSubmitting"
            novalidate
            @submit.prevent="submit"
          >
            <div class="flex flex-col items-center gap-2 text-center">
              <p class="text-xs font-semibold uppercase tracking-widest text-primary">School ERP</p>
              <h1 class="text-2xl font-bold tracking-tight">Welcome back</h1>
              <p v-if="isDev" class="text-balance text-sm text-muted-foreground">
                Or enter credentials manually. Password for all demo users matches the role name (e.g. admin123).
              </p>
              <p v-else class="text-balance text-sm text-muted-foreground">
                Staff, parents, finance, and platform administrators use one secure login.
              </p>
              <p class="text-balance text-xs text-muted-foreground">
                Using this ERP means you agree to the Platform Terms of Use for your role.
              </p>
            </div>

            <div class="space-y-4">
              <!-- Email Input -->
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
                        autocomplete="username"
                        placeholder="you@school.co.zw"
                        :disabled="isSubmitting"
                        :class="cn(formInputClass, 'pl-9')"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>

              <!-- Password Input -->
              <FormField v-slot="{ componentField }" name="password">
                <FormItem>
                  <div class="flex items-center justify-between">
                    <FormLabel :class="formLabelClass">Password</FormLabel>
                    <a
                      href="#"
                      class="text-xs underline-offset-2 hover:underline text-muted-foreground"
                    >
                      Forgot your password?
                    </a>
                  </div>
                  <FormControl>
                    <div class="relative w-full">
                      <Lock
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                      />
                      <Input
                        v-bind="componentField"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        :disabled="isSubmitting"
                        :class="cn(formInputClass, 'pl-9')"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              </FormField>
            </div>

            <!-- Dev mode inline helper quick-fill buttons -->
            <div v-if="isDev" class="flex flex-wrap gap-1.5">
              <Button
                v-for="account in WEB_DEMO_ACCOUNTS"
                :key="`fill-${account.email}`"
                type="button"
                variant="ghost"
                size="sm"
                class="h-7 px-2 text-xs"
                @click="fillAccount(account)"
              >
                Fill {{ account.label }}
              </Button>
            </div>

            <!-- Submit Action -->
            <Button
              type="submit"
              :class="cn(formButtonClass, 'w-full')"
              :disabled="isSubmitting"
              :aria-busy="isSubmitting"
            >
              <Loader2 v-if="isSubmitting" class="mr-2 size-4 animate-spin" aria-hidden="true" />
              {{ isSubmitting ? 'Signing in…' : 'Sign in' }}
            </Button>
          </form>

          <!-- Column 2: Decorative Background Image (hidden on mobile) -->
          <div class="bg-muted relative hidden md:block">
            <img
  src="https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1000"
  alt="School Campus Banner"
  class="absolute inset-0 h-full w-full object-cover dark:brightness-[0.2] dark:grayscale"
/>
          </div>
        </CardContent>
      </Card>

      <!-- Shared Footer Notice -->
      <footer class="text-center text-xs text-muted-foreground px-6">
        By clicking continue, you agree to our <a href="#" class="underline underline-offset-4 hover:text-primary">Terms of Service</a>
        and <a href="#" class="underline underline-offset-4 hover:text-primary">Privacy Policy</a>.
      </footer>
    </div>
  </main>
</template>
