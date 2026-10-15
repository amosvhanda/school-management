<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { CreditCard, KeyRound, RefreshCw } from '@lucide/vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import PageShell from '@/components/layout/PageShell.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { getErrorMessage } from '@/lib/api-response'
import { fetchLicenseStatus } from '@/services/auth.service'
import type { LicenseStatus } from '@/types/api'

const loading = ref(true)
const error = ref<string | null>(null)
const license = ref<LicenseStatus | null>(null)

const statusVariant = computed(() => {
  const status = license.value?.status
  if (status === 'active') return 'default' as const
  if (status === 'grace') return 'secondary' as const
  return 'destructive' as const
})

const planLabel = computed(() => {
  const plan = license.value?.plan
  if (!plan) return 'No plan'
  return plan.charAt(0).toUpperCase() + plan.slice(1).replace(/_/g, ' ')
})

const expiresLabel = computed(() => {
  const value = license.value?.expires_at
  if (!value) return license.value?.plan === 'lifetime' ? 'Never' : '—'
  try {
    return new Date(value).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return value
  }
})

const needsActivation = computed(() => {
  const status = license.value?.status
  return status === 'none' || status === 'expired' || status === 'grace'
})

async function load() {
  loading.value = true
  error.value = null
  try {
    license.value = await fetchLicenseStatus()
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load subscription status')
    license.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <PageShell
    title="Subscription"
    description="Your school's current plan and license status."
  >
    <PageLoader v-if="loading" />
    <ErrorState v-else-if="error" :description="error" @retry="load" />

    <section v-else-if="license" aria-labelledby="subscription-heading" class="space-y-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="subscription-heading" class="sr-only">Plan details</h2>
        <Button type="button" variant="outline" size="sm" @click="load">
          <RefreshCw class="size-4" aria-hidden="true" />
          Refresh
        </Button>
        <Button v-if="needsActivation" as-child size="sm">
          <RouterLink to="/license/activate">
            <KeyRound class="size-4" aria-hidden="true" />
            Activate or renew
          </RouterLink>
        </Button>
      </div>

      <Card>
        <CardHeader class="flex flex-row items-start gap-4 space-y-0">
          <div
            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted"
            aria-hidden="true"
          >
            <CreditCard class="size-5 text-muted-foreground" />
          </div>
          <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <CardTitle class="text-xl">{{ planLabel }}</CardTitle>
              <Badge :variant="statusVariant" class="capitalize">
                {{ license.status }}
              </Badge>
            </div>
            <CardDescription>
              {{ license.message || 'License status for this school.' }}
            </CardDescription>
          </div>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Plan
              </dt>
              <dd class="text-sm font-medium">{{ planLabel }}</dd>
            </div>
            <div class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Expires
              </dt>
              <dd class="text-sm font-medium">{{ expiresLabel }}</dd>
            </div>
            <div class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Days remaining
              </dt>
              <dd class="text-sm font-medium">
                {{
                  license.days_remaining == null
                    ? '—'
                    : license.days_remaining
                }}
              </dd>
            </div>
            <div v-if="license.grace_ends_at" class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Grace ends
              </dt>
              <dd class="text-sm font-medium">
                {{ new Date(license.grace_ends_at).toLocaleDateString() }}
              </dd>
            </div>
            <div v-if="license.active_key?.plan_type" class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Active key plan
              </dt>
              <dd class="text-sm font-medium capitalize">
                {{ license.active_key.plan_type.replace(/_/g, ' ') }}
              </dd>
            </div>
            <div class="space-y-1">
              <dt class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Enforcement
              </dt>
              <dd class="text-sm font-medium">
                {{ license.enforcement ? 'Enabled' : 'Disabled' }}
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>
    </section>
  </PageShell>
</template>
