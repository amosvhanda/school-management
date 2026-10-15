<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Bell,
  Clock,
  KeyRound,
  Pencil,
  RefreshCw,
  Save,
  ShieldCheck,
  Upload,
} from '@lucide/vue'
import PageShell from '@/components/layout/PageShell.vue'
import PageLoader from '@/components/feedback/PageLoader.vue'
import ErrorState from '@/components/feedback/ErrorState.vue'
import DatePicker from '@/components/forms/DatePicker.vue'
import AppAvatar from '@/components/media/AppAvatar.vue'
import StudentMetricRing from '@/modules/students/components/StudentMetricRing.vue'
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
import { useNotificationStore } from '@/stores/notification.store'
import { cn } from '@/lib/utils'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { resolveMediaUrl, withCacheBust } from '@/lib/media-url'
import { profileApi, uploadsApi } from '@/services/api.service'
import {
  confirmTwoFactor,
  disableTwoFactor,
  enableTwoFactor,
  fetchTwoFactorStatus,
} from '@/services/auth.service'

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
const notificationStore = useNotificationStore()
const { user, fetchMe, logout } = useAuth()

const editingPersonal = ref(false)
const editingAddress = ref(false)
const dragOver = ref(false)

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

const twoFactorEnabled = ref(false)
const twoFactorConfirmed = ref(false)
const twoFactorBusy = ref(false)
const twoFactorSetupKey = ref('')
const twoFactorQrSvg = ref('')
const twoFactorRecoveryCodes = ref<string[]>([])
const twoFactorConfirmCode = ref('')
const twoFactorDisablePassword = ref('')

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

const avatarDisplayUrl = computed(() => {
  const raw = profile.value?.avatarUrl
  if (!raw) return null
  return withCacheBust(raw) ?? resolveMediaUrl(raw)
})

const passwordMismatch = computed(
  () =>
    passwordForm.new_password.length > 0 &&
    passwordForm.new_password_confirmation.length > 0 &&
    passwordForm.new_password !== passwordForm.new_password_confirmation,
)

const profileCompletion = computed(() => {
  if (!profile.value) return 0
  const checks = [
    form.firstName.trim(),
    form.surname.trim(),
    form.email.trim(),
    form.phone.trim(),
    form.address.trim(),
    form.dateOfBirth,
    form.gender,
    profile.value.avatarUrl,
  ]
  const filled = checks.filter((v) => Boolean(String(v ?? '').trim())).length
  return Math.round((filled / checks.length) * 100)
})

const profileCompletionHint = computed(() => {
  if (profileCompletion.value >= 100) return 'Nothing left to fill'
  if (profileCompletion.value >= 75) return 'Almost complete'
  return 'Add missing details'
})

const securityHeadline = computed(() =>
  twoFactorConfirmed.value ? 'Strong account security' : 'Standard security',
)

const unreadInbox = computed(() => notificationStore.badgeCount)

const lastLoginLabel = computed(() => 'Active session')

function genderLabel(value: string) {
  return GENDER_OPTIONS.find((o) => o.value === value)?.label ?? (value || '—')
}

function cancelPersonalEdit() {
  if (!profile.value) return
  form.firstName = profile.value.firstName ?? ''
  form.surname = profile.value.surname ?? ''
  form.email = profile.value.email ?? ''
  form.phone = profile.value.phone ?? ''
  form.dateOfBirth = profile.value.dateOfBirth ?? ''
  form.gender = (profile.value.gender ?? '').toLowerCase()
  editingPersonal.value = false
}

function cancelAddressEdit() {
  if (!profile.value) return
  form.address = profile.value.address ?? ''
  editingAddress.value = false
}

async function savePersonalSection() {
  await saveProfile()
  editingPersonal.value = false
}

async function saveAddressSection() {
  await saveProfile()
  editingAddress.value = false
}

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
    const status = await fetchTwoFactorStatus()
    twoFactorEnabled.value = status.enabled
    twoFactorConfirmed.value = status.confirmed
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load your profile')
  } finally {
    loading.value = false
  }
}

async function startTwoFactor() {
  twoFactorBusy.value = true
  try {
    const result = await enableTwoFactor()
    twoFactorEnabled.value = true
    twoFactorConfirmed.value = false
    twoFactorSetupKey.value = result.setup_key ?? ''
    twoFactorQrSvg.value = result.qr_code_svg ?? ''
    twoFactorRecoveryCodes.value = result.recovery_codes ?? []
    toast.success('Scan the QR code, then confirm with a code from your app')
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not enable two-factor authentication'))
  } finally {
    twoFactorBusy.value = false
  }
}

async function confirmTwoFactorSetup() {
  if (!twoFactorConfirmCode.value.trim()) {
    toast.error('Enter the authenticator code')
    return
  }
  twoFactorBusy.value = true
  try {
    const status = await confirmTwoFactor(twoFactorConfirmCode.value.trim())
    twoFactorEnabled.value = status.enabled
    twoFactorConfirmed.value = status.confirmed
    twoFactorConfirmCode.value = ''
    twoFactorQrSvg.value = ''
    toast.success('Two-factor authentication is now active')
    await fetchMe()
  } catch (err) {
    toast.error(getErrorMessage(err, 'Invalid confirmation code'))
  } finally {
    twoFactorBusy.value = false
  }
}

async function turnOffTwoFactor() {
  if (!twoFactorDisablePassword.value) {
    toast.error('Enter your password to disable two-factor authentication')
    return
  }
  twoFactorBusy.value = true
  try {
    const status = await disableTwoFactor(twoFactorDisablePassword.value)
    twoFactorEnabled.value = status.enabled
    twoFactorConfirmed.value = status.confirmed
    twoFactorDisablePassword.value = ''
    twoFactorRecoveryCodes.value = []
    toast.success('Two-factor authentication disabled')
    await fetchMe()
  } catch (err) {
    toast.error(getErrorMessage(err, 'Could not disable two-factor authentication'))
  } finally {
    twoFactorBusy.value = false
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

async function uploadAvatarFile(file: File) {
  if (!file.type.startsWith('image/')) {
    toast.warning('Invalid file', { description: 'Please choose an image file.' })
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.warning('File too large', { description: 'Choose an image under 5 MB.' })
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
  }
}

async function onAvatarSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  await uploadAvatarFile(file)
  input.value = ''
}

function onAvatarDrop(event: DragEvent) {
  dragOver.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) void uploadAvatarFile(file)
}

async function removeAvatar() {
  if (!profile.value?.avatarUrl) return
  uploadingAvatar.value = true
  try {
    const updated = (await profileApi.update({ avatarUrl: null })) as ProfileData
    hydrate(updated)
    await fetchMe()
    toast.success('Photo removed')
  } catch (err) {
    toast.error('Could not remove photo', { description: getErrorMessage(err) })
  } finally {
    uploadingAvatar.value = false
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
    title="Profile"
    description="Manage your photo, contact details, address, and account security."
    :breadcrumbs="['Settings', 'Profile']"
    max-width="wide"
  >
    <template #actions>
      <Button
        variant="outline"
        size="icon"
        class="size-9 rounded-xl"
        :disabled="loading"
        aria-label="Refresh profile"
        @click="loadProfile"
      >
        <RefreshCw class="size-4" :class="loading && 'animate-spin'" aria-hidden="true" />
      </Button>
    </template>

    <PageLoader v-if="loading" label="Loading your profile…" />
    <ErrorState v-else-if="error" :description="error" @retry="loadProfile" />

    <template v-else-if="profile">
      <section
        aria-label="Profile summary"
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
      >
        <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardContent class="flex items-center gap-4 p-4">
            <StudentMetricRing
              label="Profile complete"
              :value-label="`${profileCompletion}%`"
              :percent="profileCompletion"
              tone="primary"
            />
            <div class="min-w-0 space-y-1">
              <p class="text-sm font-semibold text-foreground">{{ profileCompletion }}% complete</p>
              <p class="text-xs text-primary">{{ profileCompletionHint }}</p>
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardContent class="flex items-start justify-between gap-3 p-4">
            <div class="space-y-1">
              <p class="text-lg font-semibold tracking-tight capitalize">{{ securityHeadline }}</p>
              <Badge :variant="twoFactorConfirmed ? 'success' : 'secondary'">
                {{ twoFactorConfirmed ? '2FA enabled' : '2FA off' }}
              </Badge>
            </div>
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-chart-2/15 text-chart-2">
              <ShieldCheck class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardContent class="flex items-start justify-between gap-3 p-4">
            <div class="space-y-1">
              <p class="text-lg font-semibold tracking-tight">{{ unreadInbox }} unread</p>
              <Badge variant="outline">Inbox</Badge>
            </div>
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-chart-3/15 text-chart-3">
              <Bell class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>

        <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardContent class="flex items-start justify-between gap-3 p-4">
            <div class="space-y-1">
              <p class="text-lg font-semibold tracking-tight">{{ lastLoginLabel }}</p>
              <Badge variant="outline">Last login</Badge>
            </div>
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
              <Clock class="size-5" aria-hidden="true" />
            </div>
          </CardContent>
        </Card>
      </section>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)]">
        <Card class="h-fit border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardHeader class="border-b border-border/50 pb-4">
            <CardTitle class="text-base">Personal Information</CardTitle>
            <CardDescription>Profile photo</CardDescription>
          </CardHeader>
          <CardContent class="space-y-5 pt-5">
            <div class="flex flex-col items-center gap-3 text-center">
              <AppAvatar
                :src="avatarDisplayUrl"
                :alt="profile.fullName"
                :fallback="initials"
                class="size-24"
                image-class="size-24"
                shape="rounded"
              />
              <div class="space-y-1">
                <p class="text-sm font-semibold text-foreground">{{ profile.fullName }}</p>
                <Badge variant="secondary" class="capitalize">{{ roleLabel }}</Badge>
              </div>
              <p class="text-sm font-medium text-foreground">Edit your photo</p>
              <div class="flex items-center gap-3 text-sm">
                <button
                  type="button"
                  class="text-destructive hover:underline disabled:opacity-50"
                  :disabled="!profile.avatarUrl || uploadingAvatar"
                  @click="removeAvatar"
                >
                  Delete
                </button>
                <button
                  type="button"
                  class="text-primary hover:underline disabled:opacity-50"
                  :disabled="uploadingAvatar"
                  @click="pickAvatar"
                >
                  Update
                </button>
              </div>
              <input
                ref="avatarInput"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                @change="onAvatarSelected"
              />
            </div>

            <div
              role="button"
              tabindex="0"
              :class="cn(
                'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed px-4 py-8 text-center transition-colors',
                dragOver ? 'border-primary bg-primary/5' : 'border-border/80 bg-muted/20 hover:bg-muted/35',
                uploadingAvatar && 'pointer-events-none opacity-60',
              )"
              :aria-busy="uploadingAvatar"
              @click="pickAvatar"
              @keydown.enter.prevent="pickAvatar"
              @keydown.space.prevent="pickAvatar"
              @dragover.prevent="dragOver = true"
              @dragleave.prevent="dragOver = false"
              @drop.prevent="onAvatarDrop"
            >
              <Upload class="size-8 text-muted-foreground" aria-hidden="true" />
              <p class="text-sm font-medium text-foreground">Click to upload or drag and drop</p>
              <p class="text-xs text-muted-foreground">JPG or PNG (max 5 MB)</p>
            </div>
            <p v-if="uploadingAvatar" class="text-center text-xs text-muted-foreground" aria-live="polite">
              Uploading photo…
            </p>
          </CardContent>
        </Card>

        <div class="space-y-6">
          <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
            <CardHeader class="flex flex-row items-start justify-between gap-3 space-y-0 border-b border-border/50 pb-4">
              <div>
                <CardTitle class="text-base">Personal Information</CardTitle>
                <CardDescription>Name, email, and contact details</CardDescription>
              </div>
              <Button
                v-if="!editingPersonal"
                type="button"
                variant="default"
                size="sm"
                class="shrink-0 rounded-xl"
                @click="editingPersonal = true"
              >
                <Pencil class="mr-1.5 size-3.5" aria-hidden="true" />
                Edit
              </Button>
            </CardHeader>
            <CardContent class="pt-5">
              <form v-if="editingPersonal" class="space-y-5" @submit.prevent="savePersonalSection">
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="firstName">First name</Label>
                    <Input id="firstName" v-model="form.firstName" required autocomplete="given-name" />
                  </div>
                  <div class="space-y-2">
                    <Label for="surname">Last name</Label>
                    <Input id="surname" v-model="form.surname" required autocomplete="family-name" />
                  </div>
                  <div class="space-y-2 sm:col-span-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" v-model="form.email" type="email" required autocomplete="email" />
                  </div>
                  <div class="space-y-2">
                    <Label for="phone">Phone number</Label>
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
                  <div class="space-y-2 sm:col-span-2">
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
                </div>
                <div class="flex justify-end gap-2">
                  <Button type="button" variant="outline" @click="cancelPersonalEdit">Cancel</Button>
                  <Button type="submit" :disabled="savingProfile" :aria-busy="savingProfile">
                    <Save class="mr-2 size-4" aria-hidden="true" />
                    {{ savingProfile ? 'Saving…' : 'Save' }}
                  </Button>
                </div>
              </form>
              <dl v-else class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                  <dt class="text-sm text-muted-foreground">First name</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.firstName || '—' }}
                  </dd>
                </div>
                <div class="space-y-1.5">
                  <dt class="text-sm text-muted-foreground">Last name</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.surname || '—' }}
                  </dd>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                  <dt class="text-sm text-muted-foreground">Email address</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.email || '—' }}
                  </dd>
                </div>
                <div class="space-y-1.5">
                  <dt class="text-sm text-muted-foreground">Phone number</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.phone || '—' }}
                  </dd>
                </div>
                <div class="space-y-1.5">
                  <dt class="text-sm text-muted-foreground">Date of birth</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.dateOfBirth ? formatDate(form.dateOfBirth) : '—' }}
                  </dd>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                  <dt class="text-sm text-muted-foreground">Gender</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ genderLabel(form.gender) }}
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
            <CardHeader class="flex flex-row items-start justify-between gap-3 space-y-0 border-b border-border/50 pb-4">
              <div>
                <CardTitle class="text-base">Address Information</CardTitle>
                <CardDescription>Where you can be reached</CardDescription>
              </div>
              <Button
                v-if="!editingAddress"
                type="button"
                variant="default"
                size="sm"
                class="shrink-0 rounded-xl"
                @click="editingAddress = true"
              >
                <Pencil class="mr-1.5 size-3.5" aria-hidden="true" />
                Edit
              </Button>
            </CardHeader>
            <CardContent class="pt-5">
              <form v-if="editingAddress" class="space-y-4" @submit.prevent="saveAddressSection">
                <div class="space-y-2">
                  <Label for="address">Address</Label>
                  <Input id="address" v-model="form.address" autocomplete="street-address" />
                </div>
                <div class="flex justify-end gap-2">
                  <Button type="button" variant="outline" @click="cancelAddressEdit">Cancel</Button>
                  <Button type="submit" :disabled="savingProfile" :aria-busy="savingProfile">
                    <Save class="mr-2 size-4" aria-hidden="true" />
                    {{ savingProfile ? 'Saving…' : 'Save' }}
                  </Button>
                </div>
              </form>
              <dl v-else class="space-y-4">
                <div class="space-y-1.5">
                  <dt class="text-sm text-muted-foreground">Address</dt>
                  <dd class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5 text-sm font-medium">
                    {{ form.address || '—' }}
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <Card v-if="isTeacher" class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
            <CardHeader class="border-b border-border/50 pb-4">
              <CardTitle class="text-base">Employment</CardTitle>
              <CardDescription>Managed by your school administration</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 pt-5 text-sm sm:grid-cols-2">
              <div class="space-y-1">
                <p class="text-muted-foreground">Employee ID</p>
                <p class="font-medium">{{ profile.employeeId || '—' }}</p>
              </div>
              <div class="space-y-1">
                <p class="text-muted-foreground">Department</p>
                <p class="font-medium">{{ profile.department || '—' }}</p>
              </div>
              <div class="space-y-1">
                <p class="text-muted-foreground">Qualification</p>
                <p class="font-medium">{{ profile.qualification || '—' }}</p>
              </div>
              <div class="space-y-1">
                <p class="text-muted-foreground">Joined</p>
                <p class="font-medium">{{ profile.joiningDate ? formatDate(profile.joiningDate) : '—' }}</p>
              </div>
              <div class="space-y-2 sm:col-span-2">
                <p class="text-muted-foreground">Assigned subjects</p>
                <div v-if="profile.subjects?.length" class="flex flex-wrap gap-1.5">
                  <Badge v-for="subject in profile.subjects" :key="subject" variant="outline">{{ subject }}</Badge>
                </div>
                <p v-else class="text-muted-foreground">No subjects assigned.</p>
              </div>
            </CardContent>
          </Card>

        <Card class="border-border/70 shadow-[var(--shadow-soft)] ring-1 ring-border/60">
          <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
              <ShieldCheck class="size-4 text-muted-foreground" aria-hidden="true" />
              Security
            </CardTitle>
            <CardDescription>
              Change your password or enable two-factor authentication for stronger account protection.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-8">
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

            <section class="space-y-4 border-t border-border/60 pt-6" aria-labelledby="two-factor-heading">
              <div class="space-y-1">
                <h3 id="two-factor-heading" class="text-sm font-semibold text-foreground">
                  Two-factor authentication
                </h3>
                <p class="text-sm text-muted-foreground">
                  {{
                    twoFactorConfirmed
                      ? 'Enabled. Sign-in requires an authenticator code.'
                      : twoFactorEnabled
                        ? 'Setup started — confirm with a code from your authenticator app.'
                        : 'Add an authenticator app for an extra login step.'
                  }}
                </p>
              </div>

              <div v-if="twoFactorQrSvg" class="space-y-3 rounded-xl border border-border/60 p-4">
                <div class="mx-auto max-w-[180px]" v-html="twoFactorQrSvg" />
                <p class="text-xs text-muted-foreground">
                  Setup key: <code class="rounded bg-muted px-1">{{ twoFactorSetupKey }}</code>
                </p>
                <ul v-if="twoFactorRecoveryCodes.length" class="grid gap-1 text-xs text-muted-foreground sm:grid-cols-2">
                  <li v-for="code in twoFactorRecoveryCodes" :key="code">
                    <code>{{ code }}</code>
                  </li>
                </ul>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                  <div class="flex-1 space-y-2">
                    <Label for="two-factor-confirm">Confirmation code</Label>
                    <Input
                      id="two-factor-confirm"
                      v-model="twoFactorConfirmCode"
                      inputmode="numeric"
                      autocomplete="one-time-code"
                      placeholder="123456"
                    />
                  </div>
                  <Button type="button" :disabled="twoFactorBusy" @click="confirmTwoFactorSetup">
                    Confirm
                  </Button>
                </div>
              </div>

              <div v-else-if="twoFactorConfirmed" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1 space-y-2">
                  <Label for="two-factor-disable-password">Password to disable</Label>
                  <Input
                    id="two-factor-disable-password"
                    v-model="twoFactorDisablePassword"
                    type="password"
                    autocomplete="current-password"
                  />
                </div>
                <Button
                  type="button"
                  variant="outline"
                  :disabled="twoFactorBusy"
                  @click="turnOffTwoFactor"
                >
                  Disable 2FA
                </Button>
              </div>

              <Button
                v-else
                type="button"
                :disabled="twoFactorBusy"
                @click="startTwoFactor"
              >
                {{ twoFactorBusy ? 'Working…' : 'Enable two-factor authentication' }}
              </Button>
            </section>
          </CardContent>
        </Card>
        </div>
      </div>
    </template>
  </PageShell>
</template>
