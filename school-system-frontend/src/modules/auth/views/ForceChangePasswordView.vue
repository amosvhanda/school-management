<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { KeyRound, Loader2, LogOut } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import PasswordInput from '@/components/forms/PasswordInput.vue'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { useAuthStore } from '@/stores/auth.store'
import { profileApi } from '@/services/api.service'
import { brandName } from '@/lib/brand'
import { formInputClass } from '@/lib/form-standards'

const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const submitting = ref(false)
const form = ref({
  old_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const mismatch = computed(
  () =>
    form.value.new_password.length > 0
    && form.value.new_password_confirmation.length > 0
    && form.value.new_password !== form.value.new_password_confirmation,
)

async function submit() {
  if (!form.value.old_password || !form.value.new_password) {
    toast.error('Missing fields', 'Fill in all password fields.')
    return
  }
  if (mismatch.value) {
    toast.error('Passwords do not match', 'The new password and confirmation must be identical.')
    return
  }

  submitting.value = true
  try {
    await profileApi.changePassword({
      old_password: form.value.old_password,
      new_password: form.value.new_password,
      new_password_confirmation: form.value.new_password_confirmation,
    })
    toast.success('Password updated', 'Please sign in again with your new password.')
    await auth.logout()
    await router.replace({ name: 'login' })
  } catch (err) {
    toast.error('Could not change password', getErrorMessage(err))
  } finally {
    submitting.value = false
  }
}

async function signOut() {
  await auth.logout()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <main class="mx-auto flex w-full max-w-lg flex-col gap-6 px-4 py-6 sm:px-6">
    <Card>
      <CardHeader>
        <div class="flex items-start gap-3">
          <div class="rounded-lg bg-primary/10 p-2 text-primary" aria-hidden="true">
            <KeyRound class="size-5" />
          </div>
          <div class="min-w-0 space-y-1">
            <CardTitle class="text-balance">Change your temporary password</CardTitle>
            <CardDescription class="text-pretty">
              {{ brandName }} requires a new password before you can continue. Enter the temporary
              password you were given, then choose a new one.
            </CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <form class="space-y-4" @submit.prevent="submit" aria-describedby="force-password-help">
          <p id="force-password-help" class="sr-only">
            You must change your temporary password before using the application.
          </p>
          <div class="space-y-2">
            <Label for="force_old_password">Current (temporary) password</Label>
            <PasswordInput
              id="force_old_password"
              :model-value="form.old_password"
              autocomplete="current-password"
              required
              :class="formInputClass"
              :disabled="submitting"
              @update:model-value="form.old_password = String($event ?? '')"
            />
          </div>
          <div class="space-y-2">
            <Label for="force_new_password">New password</Label>
            <PasswordInput
              id="force_new_password"
              :model-value="form.new_password"
              autocomplete="new-password"
              required
              :class="formInputClass"
              :disabled="submitting"
              @update:model-value="form.new_password = String($event ?? '')"
            />
          </div>
          <div class="space-y-2">
            <Label for="force_new_password_confirmation">Confirm new password</Label>
            <PasswordInput
              id="force_new_password_confirmation"
              :model-value="form.new_password_confirmation"
              autocomplete="new-password"
              required
              :class="formInputClass"
              :disabled="submitting"
              :aria-invalid="mismatch ? 'true' : undefined"
              :aria-describedby="mismatch ? 'force-password-mismatch' : undefined"
              @update:model-value="form.new_password_confirmation = String($event ?? '')"
            />
            <p
              v-if="mismatch"
              id="force-password-mismatch"
              class="text-sm text-destructive"
              role="alert"
            >
              Passwords do not match.
            </p>
          </div>
          <Button
            type="submit"
            class="w-full"
            :disabled="submitting || mismatch"
            :aria-busy="submitting"
          >
            <Loader2 v-if="submitting" class="size-4 animate-spin" aria-hidden="true" />
            {{ submitting ? 'Updating…' : 'Update password' }}
          </Button>
        </form>
      </CardContent>

      <CardFooter class="justify-between gap-3 border-t pt-6">
        <p class="text-sm text-muted-foreground">
          Signed in as {{ auth.user?.email }}
        </p>
        <Button type="button" variant="ghost" @click="signOut">
          <LogOut class="size-4" aria-hidden="true" />
          Sign out
        </Button>
      </CardFooter>
    </Card>
  </main>
</template>
