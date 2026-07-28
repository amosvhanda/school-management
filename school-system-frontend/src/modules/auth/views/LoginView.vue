<script setup lang="ts">
import { computed, onMounted, ref, type HTMLAttributes } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { GraduationCap, Loader2, Lock, Mail } from '@lucide/vue'
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
import AuthFormShell from '@/components/auth/AuthFormShell.vue'
import PasswordInput from '@/components/forms/PasswordInput.vue'
import { useFormApiSubmit } from '@/composables/useFormApiSubmit'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { resolvePostLoginRedirect } from '@/app/router/guards'
import { isStaffDashboardRole } from '@/lib/permissions'
import { DEMO_ACCOUNTS, type DemoAccount } from '@/lib/demo-accounts'
import { brandName, brandTagline } from '@/lib/brand'
import {
  formButtonClass,
  formFieldsAnimateOptions,
  formInputClass,
  formLabelClass,
} from '@/lib/form-standards'
import { loginFormSchema } from '@/modules/auth/auth-form'
import { useConfigStore } from '@/stores/config.store'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const router = useRouter()
const route = useRoute()
const { login, logout } = useAuth()
const configStore = useConfigStore()
const toast = useToast()
const isDev = computed(() => import.meta.env.DEV)
const quickLoginRole = ref<string | null>(null)
const tenantBrandName = computed(() =>
  String(configStore.settings?.branding?.school_name ?? '').trim() || brandName,
)
const tenantBrandTagline = computed(() =>
  String(configStore.settings?.branding?.motto ?? '').trim() || brandTagline,
)

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
    const next = typeof route.query.redirect === 'string' ? route.query.redirect : undefined
    await router.push({
      name: 'platform-terms-accept',
      query: next && next !== '/terms/accept' ? { redirect: next } : undefined,
    })
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

onMounted(() => {
  if (configStore.loaded) return
  void configStore.fetchPublicConfig().catch(() => {
    configStore.markLoadedWithoutSchool()
  })
})
</script>

<template>
  <main id="main-content" tabindex="-1" class="flex min-h-svh items-center justify-center p-6 md:p-10">
    <AuthFormShell :class="props.class">
      <template #above>
        <Card v-if="isDev" class="border-border/70 bg-card/80 shadow-[var(--shadow-soft)] backdrop-blur-sm">
          <CardHeader class="space-y-1 pb-3">
            <CardTitle class="text-sm font-medium">Test profiles</CardTitle>
            <CardDescription class="text-xs">
              One-click sign-in for each role. Seed with
              <code class="rounded bg-muted px-1 py-0.5 font-mono text-[11px]">php artisan db:seed --class=UserSeeder</code>
              if needed.
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
                class="h-auto flex-col items-start gap-0.5 border-border/70 bg-background/60 px-2 py-2 text-left whitespace-normal"
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
      </template>

      <form
        v-auto-animate="formFieldsAnimateOptions"
        class="space-y-6"
        :aria-busy="isSubmitting"
        novalidate
        @submit.prevent="submit"
      >
        <div class="flex flex-col items-center gap-3 text-center md:items-start md:text-left">
          <div
            class="flex size-11 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            aria-hidden="true"
          >
            <GraduationCap class="size-5" />
          </div>
          <div class="space-y-1.5">
            <p class="font-heading text-lg font-semibold tracking-tight text-primary">
              {{ tenantBrandName }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ tenantBrandTagline }}
            </p>
            <h1 class="font-heading text-2xl font-semibold tracking-tight md:text-[1.75rem]">
              Welcome back
            </h1>
            <p class="text-balance text-sm text-muted-foreground">
              Staff, parents, finance, and platform administrators use one secure login.
            </p>
          </div>
        </div>

        <div class="space-y-4">
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

          <FormField v-slot="{ componentField }" name="password">
            <FormItem>
              <div class="flex items-center justify-between gap-2">
                <FormLabel :class="formLabelClass">Password</FormLabel>
                <RouterLink
                  to="/forgot-password"
                  class="rounded-sm text-xs text-muted-foreground underline-offset-2 hover:text-foreground hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                  Forgot your password?
                </RouterLink>
              </div>
              <FormControl>
                <PasswordInput
                  v-bind="componentField"
                  autocomplete="current-password"
                  placeholder="Enter your password"
                  :disabled="isSubmitting"
                  :class="cn(formInputClass, 'pl-9')"
                >
                  <template #leading>
                    <Lock
                      class="pointer-events-none absolute top-1/2 left-2.5 z-[1] size-4 -translate-y-1/2 text-muted-foreground"
                      aria-hidden="true"
                    />
                  </template>
                </PasswordInput>
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>
        </div>

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
