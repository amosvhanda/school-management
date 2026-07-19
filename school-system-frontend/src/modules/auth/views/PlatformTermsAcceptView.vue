<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { FileCheck2, Loader2, LogOut } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { resolvePostLoginRedirect } from '@/app/router/guards'
import { useAuthStore } from '@/stores/auth.store'
import {
  acceptPlatformTerms,
  fetchPlatformTerms,
  type PlatformTermsPayload,
} from '@/services/auth.service'

const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const loading = ref(true)
const submitting = ref(false)
const agreed = ref(false)
const error = ref<string | null>(null)
const terms = ref<PlatformTermsPayload | null>(null)

async function load() {
  loading.value = true
  error.value = null
  try {
    terms.value = await fetchPlatformTerms()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load platform terms')
  } finally {
    loading.value = false
  }
}

function setAgreed(value: boolean | 'indeterminate') {
  agreed.value = value === true
}

function toggleAgreed() {
  agreed.value = !agreed.value
}

function safeRedirectTarget(): string | null {
  const requested = typeof router.currentRoute.value.query.redirect === 'string'
    ? router.currentRoute.value.query.redirect
    : null

  if (!requested) return null
  if (requested === '/terms/accept' || requested.startsWith('/terms/accept?')) return null
  if (requested === '/login' || requested.startsWith('/login?')) return null
  return requested
}

async function accept() {
  if (!terms.value) {
    toast.error('Terms are still loading. Please wait a moment.')
    return
  }

  if (!agreed.value) {
    toast.error('Please tick the box to confirm you agree')
    return
  }

  submitting.value = true
  try {
    const user = await acceptPlatformTerms(terms.value.version)
    if (!user?.id) {
      throw new Error('Acceptance saved, but user session could not be refreshed. Sign in again.')
    }
    auth.setUser({
      ...user,
      platform_terms_accepted: true,
    })
    toast.success('Terms accepted — welcome to the School ERP')
    const redirect = resolvePostLoginRedirect(
      user,
      safeRedirectTarget(),
      (path) => router.resolve(path),
    )
    await router.replace(redirect)
  } catch (err) {
    toast.error('Could not accept terms', getErrorMessage(err))
  } finally {
    submitting.value = false
  }
}

async function signOut() {
  await auth.logout()
  await router.replace({ name: 'login' })
}

onMounted(load)
</script>

<template>
  <main class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-6 sm:px-6">
    <Card class="overflow-hidden">
      <CardHeader>
        <div class="flex items-start gap-3">
          <div class="rounded-lg bg-primary/10 p-2 text-primary">
            <FileCheck2 class="size-5" aria-hidden="true" />
          </div>
          <div class="min-w-0 space-y-1">
            <CardTitle class="text-balance">{{ terms?.title || 'Platform Terms of Use' }}</CardTitle>
            <CardDescription class="text-pretty">
              {{ terms?.summary || 'You must agree to the School ERP terms before using the platform.' }}
            </CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent class="space-y-4">
        <div
          v-if="loading"
          class="flex items-center gap-2 text-sm text-muted-foreground"
          role="status"
          aria-live="polite"
        >
          <Loader2 class="size-4 animate-spin" aria-hidden="true" />
          Loading terms…
        </div>

        <div
          v-else-if="error"
          class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
          role="alert"
        >
          <p>{{ error }}</p>
          <Button class="mt-3" variant="outline" size="sm" @click="load">Try again</Button>
        </div>

        <template v-else-if="terms">
          <p class="text-xs text-muted-foreground">Version {{ terms.version }}</p>
          <div
            class="max-h-[min(45vh,24rem)] overflow-y-auto rounded-lg border bg-muted/30 p-4"
            tabindex="0"
            aria-label="Platform terms of use"
          >
            <pre class="whitespace-pre-wrap font-sans text-sm leading-relaxed text-foreground">{{ terms.content }}</pre>
          </div>

          <div
            class="flex w-full cursor-pointer items-start gap-3 rounded-lg border p-4 text-left transition-colors hover:bg-muted/40"
            :class="agreed ? 'border-primary bg-primary/5' : ''"
            role="group"
            @click="toggleAgreed"
          >
            <Checkbox
              id="agree-platform-terms"
              class="mt-0.5"
              :checked="agreed"
              :aria-checked="agreed"
              @click.stop
              @keydown.enter.prevent.stop="toggleAgreed"
              @keydown.space.prevent.stop="toggleAgreed"
              @update:checked="setAgreed"
            />
            <div class="min-w-0 space-y-1">
              <p class="text-sm font-medium leading-snug">
                I agree to all School ERP Platform Terms of Use
              </p>
              <p class="text-xs text-muted-foreground">
                By continuing you accept the full terms for using this ERP platform in your role.
              </p>
            </div>
          </div>
        </template>
      </CardContent>

      <CardFooter class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
        <Button
          variant="outline"
          class="w-full sm:w-auto"
          :disabled="submitting"
          @click="signOut"
        >
          <LogOut class="size-4" aria-hidden="true" />
          Sign out
        </Button>
        <Button
          class="w-full sm:w-auto"
          :disabled="loading || !!error || !terms || submitting"
          @click="accept"
        >
          <Loader2 v-if="submitting" class="size-4 animate-spin" aria-hidden="true" />
          {{ submitting ? 'Saving…' : 'Agree and continue' }}
        </Button>
      </CardFooter>
    </Card>
  </main>
</template>
