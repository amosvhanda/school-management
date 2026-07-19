<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ArrowLeft, FileText, Loader2, Shield } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { getErrorMessage } from '@/lib/api-response'
import {
  fetchPlatformTerms,
  fetchPrivacyPolicy,
  type PlatformTermsPayload,
} from '@/services/auth.service'

const route = useRoute()
const loading = ref(true)
const error = ref<string | null>(null)
const document = ref<PlatformTermsPayload | null>(null)

const kind = computed(() => (route.meta.document === 'privacy' ? 'privacy' : 'terms'))

const icon = computed(() => (kind.value === 'privacy' ? Shield : FileText))

const backLabel = computed(() =>
  kind.value === 'privacy' ? 'Back to login' : 'Back to login',
)

async function load() {
  loading.value = true
  error.value = null
  try {
    document.value = kind.value === 'privacy'
      ? await fetchPrivacyPolicy()
      : await fetchPlatformTerms()
  } catch (err) {
    document.value = null
    error.value = getErrorMessage(
      err,
      kind.value === 'privacy' ? 'Failed to load privacy policy' : 'Failed to load terms of service',
    )
  } finally {
    loading.value = false
  }
}

watch(kind, () => {
  void load()
})

onMounted(load)
</script>

<template>
  <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6">
    <div class="flex items-center justify-between gap-3">
      <Button variant="ghost" size="sm" as-child>
        <RouterLink to="/login" class="inline-flex items-center gap-2">
          <ArrowLeft class="size-4" aria-hidden="true" />
          {{ backLabel }}
        </RouterLink>
      </Button>
      <nav class="flex gap-2 text-sm" aria-label="Legal documents">
        <RouterLink
          to="/legal/terms"
          class="rounded-md px-2 py-1 text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          :class="{ 'font-medium text-foreground': kind === 'terms' }"
        >
          Terms
        </RouterLink>
        <RouterLink
          to="/legal/privacy"
          class="rounded-md px-2 py-1 text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          :class="{ 'font-medium text-foreground': kind === 'privacy' }"
        >
          Privacy
        </RouterLink>
      </nav>
    </div>

    <PageLoader v-if="loading" :label="kind === 'privacy' ? 'Loading privacy policy' : 'Loading terms'" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <Card v-else-if="document">
      <CardHeader>
        <div class="flex items-start gap-3">
          <div class="rounded-lg bg-primary/10 p-2 text-primary">
            <component :is="icon" class="size-5" aria-hidden="true" />
          </div>
          <div class="min-w-0 space-y-1">
            <CardTitle class="text-balance">{{ document.title }}</CardTitle>
            <CardDescription>{{ document.summary }}</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <p class="text-xs text-muted-foreground">Version {{ document.version }}</p>
        <div
          class="max-h-[min(70vh,40rem)] overflow-y-auto rounded-lg border bg-muted/30 p-4"
          :aria-label="document.title"
          tabindex="0"
        >
          <pre class="whitespace-pre-wrap font-sans text-sm leading-relaxed text-foreground">{{ document.content }}</pre>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button as-child>
            <RouterLink to="/login">Return to login</RouterLink>
          </Button>
          <Button variant="outline" as-child>
            <RouterLink :to="kind === 'privacy' ? '/legal/terms' : '/legal/privacy'">
              {{ kind === 'privacy' ? 'View Terms of Service' : 'View Privacy Policy' }}
            </RouterLink>
          </Button>
        </div>
      </CardContent>
    </Card>

    <p v-else-if="!loading && !error" class="flex items-center gap-2 text-sm text-muted-foreground">
      <Loader2 class="size-4 animate-spin" aria-hidden="true" />
      Preparing document…
    </p>
  </main>
</template>
