<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Camera, KeyRound, Save, ShieldCheck, UserCog } from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { toast } from 'vue-sonner'
import { useAuth } from '@/composables/useAuth'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { profileApi, uploadsApi } from '@/services/api.service'

interface ProfileData {
  id: number
  firstName: string
  surname: string
  fullName: string
  email: string
  phone: string
  address: string
  role: string
  dateOfBirth: string
  gender: string
  employeeId?: string
  department?: string
  qualification?: string
  employmentType?: string
  joiningDate?: string
  employmentStatus?: string
  subjects?: string[]
  classes?: string[]
  avatarUrl?: string
}

const GENDER_OPTIONS = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' },
  { value: 'not specified', label: 'Prefer not to say' },
]

const router = useRouter()
const { user, fetchMe, logout } = useAuth()

const loading = ref(true)
const error = ref<string | null>(null)
const profile = ref<ProfileData | null>(null)
const savingProfile = ref(false)
const changingPassword = ref(false)
const uploadingAvatar = ref(false)
const avatarInput = ref<HTMLInputElement | null>(null)

const todayIso = new Date().toISOString().slice(0, 10)

const form = reactive({
  firstName: '',
  surname: '',
  email: '',
  phone: '',
  address: '',
  dateOfBirth: '',
  gender: '',
})

const passwordForm = reactive({
  old_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const isTeacher = computed(() => profile.value?.role === 'teacher')

const initials = computed(() => {
  const name = profile.value?.fullName || user.value?.name || 'User'
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
})

const roleLabel = computed(() =>
  (profile.value?.role ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
)

const passwordMismatch = computed(
  () =>
    passwordForm.new_password.length > 0 &&
    passwordForm.new_password_confirmation.length > 0 &&
    passwordForm.new_password !== passwordForm.new_password_confirmation,
)

function hydrate(data: ProfileData) {
  profile.value = data
  form.firstName = data.firstName ?? ''
  form.surname = data.surname ?? ''
  form.email = data.email ?? ''
  form.phone = data.phone ?? ''
  form.address = data.address ?? ''
  form.dateOfBirth = data.dateOfBirth ?? ''
  form.gender = (data.gender ?? '').toLowerCase()
}

async function loadProfile() {
  loading.value = true
  error.value = null
  try {
    hydrate((await profileApi.show()) as ProfileData)
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load your profile')
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  if (!form.firstName.trim() || !form.surname.trim()) {
    toast.warning('Name required', { description: 'First name and surname cannot be empty.' })
    return
  }
  if (!form.email.trim()) {
    toast.warning('Email required', { description: 'Please provide a valid email address.' })
    return
  }

  savingProfile.value = true
  try {
    const updated = (await profileApi.update({
      firstName: form.firstName.trim(),
      surname: form.surname.trim(),
      email: form.email.trim(),
      phone: form.phone.trim() || null,
      address: form.address.trim() || null,
      dateOfBirth: form.dateOfBirth || null,
      gender: form.gender || null,
    })) as ProfileData
    hydrate(updated)
    await fetchMe()
    toast.success('Profile updated')
  } catch (err) {
    toast.error('Could not save profile', { description: getErrorMessage(err) })
  } finally {
    savingProfile.value = false
  }
}

function pickAvatar() {
  avatarInput.value?.click()
}

async function onAvatarSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    toast.warning('Invalid file', { description: 'Please choose an image file.' })
    input.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.warning('File too large', { description: 'Choose an image under 5 MB.' })
    input.value = ''
    return
  }

  uploadingAvatar.value = true
  try {
    const uploaded = await uploadsApi.upload(file, 'avatars')
    const updated = (await profileApi.update({ avatarUrl: uploaded.url })) as ProfileData
    hydrate(updated)
    await fetchMe()
    toast.success('Photo updated')
  } catch (err) {
    toast.error('Could not upload photo', { description: getErrorMessage(err) })
  } finally {
    uploadingAvatar.value = false
    input.value = ''
  }
}

async function changePassword() {
  if (!passwordForm.old_password || !passwordForm.new_password) {
    toast.warning('Missing fields', { description: 'Fill in all password fields.' })
    return
  }
  if (passwordMismatch.value) {
    toast.warning('Passwords do not match', {
      description: 'The new password and confirmation must be identical.',
    })
    return
  }

  changingPassword.value = true
  try {
    await profileApi.changePassword({
      old_password: passwordForm.old_password,
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.new_password_confirmation,
    })
    toast.success('Password changed', { description: 'Please sign in again with your new password.' })
    await logout()
    await router.push({ name: 'login' })
  } catch (err) {
    toast.error('Could not change password', { description: getErrorMessage(err) })
  } finally {
    changingPassword.value = false
  }
}

onMounted(loadProfile)
</script>

<template>
  <PageShell
    title="My Profile"
    description="View and update your personal details, employment information, and password."
  >
    <PageLoader v-if="loading" label="Loading your profile…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadProfile" />

    <div v-else-if="profile" class="grid gap-6 lg:grid-cols-[20rem_1fr]">
      <aside class="space-y-6">
        <Card class="border-border/70 shadow-sm">
          <CardContent class="flex flex-col items-center gap-4 px-6 py-8 text-center">
            <div class="relative">
              <Avatar class="size-20">
                <AvatarImage v-if="profile.avatarUrl" :src="profile.avatarUrl" :alt="profile.fullName" />
                <AvatarFallback class="text-xl font-semibold">{{ initials }}</AvatarFallback>
              </Avatar>
              <button
                type="button"
                class="absolute -right-1 -bottom-1 flex size-8 items-center justify-center rounded-full border border-border bg-background text-muted-foreground shadow-sm transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-60"
                :disabled="uploadingAvatar"
                :aria-busy="uploadingAvatar"
                aria-label="Change profile photo"
                @click="pickAvatar"
              >
                <Camera class="size-4" aria-hidden="true" />
              </button>
              <input
                ref="avatarInput"
                type="file"
                accept="image/*"
                class="sr-only"
                @change="onAvatarSelected"
              />
            </div>
            <div class="space-y-1">
              <p class="text-lg font-semibold text-foreground">{{ profile.fullName }}</p>
              <Badge variant="secondary" class="capitalize">{{ roleLabel }}</Badge>
              <p v-if="uploadingAvatar" class="text-xs text-muted-foreground" aria-live="polite">
                Uploading photo…
              </p>
            </div>
            <dl class="w-full space-y-2 pt-2 text-left text-sm">
              <div v-if="profile.employeeId" class="flex justify-between gap-2">
                <dt class="text-muted-foreground">Employee ID</dt>
                <dd class="font-medium">{{ profile.employeeId }}</dd>
              </div>
              <div v-if="profile.department" class="flex justify-between gap-2">
                <dt class="text-muted-foreground">Department</dt>
                <dd class="font-medium">{{ profile.department }}</dd>
              </div>
              <div v-if="profile.employmentStatus" class="flex justify-between gap-2">
                <dt class="text-muted-foreground">Status</dt>
                <dd class="font-medium capitalize">{{ profile.employmentStatus }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>

        <Card v-if="isTeacher" class="border-border/70 shadow-sm">
          <CardHeader class="pb-3">
            <CardTitle class="text-base">Employment</CardTitle>
            <CardDescription>Read-only. Contact administration to update.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4 text-sm">
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Qualification
              </p>
              <p class="font-medium">{{ profile.qualification || '—' }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Employment type
              </p>
              <p class="font-medium capitalize">{{ profile.employmentType || '—' }}</p>
            </div>
            <div class="space-y-1">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Joined
              </p>
              <p class="font-medium">
                {{ profile.joiningDate ? formatDate(profile.joiningDate) : '—' }}
              </p>
            </div>
            <div class="space-y-1.5">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Assigned subjects
              </p>
              <div v-if="profile.subjects?.length" class="flex flex-wrap gap-1.5">
                <Badge v-for="subject in profile.subjects" :key="subject" variant="outline">
                  {{ subject }}
                </Badge>
              </div>
              <p v-else class="text-sm text-muted-foreground">No subjects assigned.</p>
            </div>
            <div class="space-y-1.5">
              <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                Assigned classes
              </p>
              <div v-if="profile.classes?.length" class="flex flex-wrap gap-1.5">
                <Badge v-for="cls in profile.classes" :key="cls" variant="outline">
                  {{ cls }}
                </Badge>
              </div>
              <p v-else class="text-sm text-muted-foreground">No classes assigned.</p>
            </div>
          </CardContent>
        </Card>
      </aside>

      <div class="space-y-6">
        <Card class="border-border/70 shadow-sm">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
              <UserCog class="size-4 text-muted-foreground" aria-hidden="true" />
              Personal information
            </CardTitle>
            <CardDescription>Update your name and contact details.</CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-5" @submit.prevent="saveProfile">
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                  <Label for="firstName">First name</Label>
                  <Input id="firstName" v-model="form.firstName" required autocomplete="given-name" />
                </div>
                <div class="space-y-2">
                  <Label for="surname">Surname</Label>
                  <Input id="surname" v-model="form.surname" required autocomplete="family-name" />
                </div>
                <div class="space-y-2">
                  <Label for="email">Email</Label>
                  <Input id="email" v-model="form.email" type="email" required autocomplete="email" />
                </div>
                <div class="space-y-2">
                  <Label for="phone">Phone</Label>
                  <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="e.g. 0771234567"
                  />
                </div>
                <div class="space-y-2">
                  <Label for="dob">Date of birth</Label>
                  <DatePicker id="dob" v-model="form.dateOfBirth" :max="todayIso" placeholder="Select date" />
                </div>
                <div class="space-y-2">
                  <Label for="gender">Gender</Label>
                  <Select v-model="form.gender">
                    <SelectTrigger id="gender" class="h-10">
                      <SelectValue placeholder="Select gender" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="opt in GENDER_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2 sm:col-span-2">
                  <Label for="address">Address</Label>
                  <Input id="address" v-model="form.address" autocomplete="street-address" />
                </div>
              </div>

              <div class="flex justify-end">
                <Button type="submit" :disabled="savingProfile" :aria-busy="savingProfile">
                  <Save class="mr-2 size-4" aria-hidden="true" />
                  {{ savingProfile ? 'Saving…' : 'Save changes' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>

        <Card class="border-border/70 shadow-sm">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
              <ShieldCheck class="size-4 text-muted-foreground" aria-hidden="true" />
              Security
            </CardTitle>
            <CardDescription>
              Change your password. You'll be signed out and asked to log in again.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form class="space-y-5" @submit.prevent="changePassword">
              <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                  <Label for="old_password">Current password</Label>
                  <Input
                    id="old_password"
                    v-model="passwordForm.old_password"
                    type="password"
                    autocomplete="current-password"
                  />
                </div>
                <div class="space-y-2">
                  <Label for="new_password">New password</Label>
                  <Input
                    id="new_password"
                    v-model="passwordForm.new_password"
                    type="password"
                    autocomplete="new-password"
                  />
                </div>
                <div class="space-y-2">
                  <Label for="new_password_confirmation">Confirm new password</Label>
                  <Input
                    id="new_password_confirmation"
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    :aria-invalid="passwordMismatch"
                    :aria-describedby="passwordMismatch ? 'password-mismatch' : undefined"
                  />
                  <p v-if="passwordMismatch" id="password-mismatch" class="text-xs text-destructive">
                    Passwords do not match.
                  </p>
                </div>
              </div>

              <div class="flex justify-end">
                <Button
                  type="submit"
                  variant="outline"
                  :disabled="changingPassword || passwordMismatch"
                  :aria-busy="changingPassword"
                >
                  <KeyRound class="mr-2 size-4" aria-hidden="true" />
                  {{ changingPassword ? 'Updating…' : 'Change password' }}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </PageShell>
</template>
