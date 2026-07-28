<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { HardDrive, Loader2, Plus } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDateTime } from '@/lib/format'
import { platformApi, type SchoolLicenseRow } from '@/services/api.service'

const open = defineModel<boolean>('open', { default: false })

const props = defineProps<{
  school: SchoolLicenseRow | null
}>()

const emit = defineEmits<{
  changed: []
}>()

const toast = useToast()
const loading = ref(false)
const actionLoading = ref<string | null>(null)
const domains = ref<Array<Record<string, unknown>>>([])
const backups = ref<Array<Record<string, unknown>>>([])
const usage = ref<Record<string, unknown> | null>(null)
const newDomain = ref('')
const schoolStatus = ref('active')

const schoolId = computed(() => props.school?.id ?? null)
const title = computed(() => props.school?.name ?? 'School')

watch(
  () => [open.value, schoolId.value] as const,
  ([isOpen, id]) => {
    if (isOpen && id) {
      void loadDetails()
    }
  },
)

async function loadDetails() {
  if (!schoolId.value) return
  loading.value = true
  try {
    const detail = await platformApi.getSchool(schoolId.value)
    domains.value = Array.isArray(detail.domains) ? detail.domains : []
    usage.value = detail.usage ?? null
    schoolStatus.value = String(detail.school?.status ?? props.school?.status ?? 'active')
    const backupPayload = await platformApi.listSchoolBackups(schoolId.value)
    backups.value = Array.isArray(backupPayload.backups) ? backupPayload.backups : []
  } catch (err) {
    toast.error('Could not load school details', getErrorMessage(err))
  } finally {
    loading.value = false
  }
}

async function setStatus(status: 'active' | 'suspended') {
  if (!schoolId.value) return
  actionLoading.value = `status-${status}`
  try {
    await platformApi.updateSchoolStatus(schoolId.value, status)
    schoolStatus.value = status
    toast.success(status === 'active' ? 'School activated' : 'School suspended')
    emit('changed')
  } catch (err) {
    toast.error('Could not update status', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function addDomain() {
  if (!schoolId.value || !newDomain.value.trim()) return
  actionLoading.value = 'add-domain'
  try {
    await platformApi.addSchoolDomain(schoolId.value, {
      domain: newDomain.value.trim().toLowerCase(),
      is_primary: domains.value.length === 0,
    })
    newDomain.value = ''
    toast.success('Domain added')
    await loadDetails()
  } catch (err) {
    toast.error('Could not add domain', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function verifyDomain(domainId: number, force = false) {
  if (!schoolId.value) return
  actionLoading.value = `verify-${domainId}`
  try {
    await platformApi.verifySchoolDomain(schoolId.value, domainId, force)
    toast.success(force ? 'Domain verified manually' : 'Domain verified')
    await loadDetails()
  } catch (err) {
    toast.error('Domain verification failed', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function removeDomain(domainId: number) {
  if (!schoolId.value) return
  actionLoading.value = `delete-domain-${domainId}`
  try {
    await platformApi.deleteSchoolDomain(schoolId.value, domainId)
    toast.success('Domain removed')
    await loadDetails()
  } catch (err) {
    toast.error('Could not remove domain', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function createBackup() {
  if (!schoolId.value) return
  actionLoading.value = 'backup'
  try {
    await platformApi.createSchoolBackup(schoolId.value)
    toast.success('Backup created')
    await loadDetails()
  } catch (err) {
    toast.error('Could not create backup', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function restoreBackup(backupId: number) {
  if (!schoolId.value) return
  if (!window.confirm('Restore this backup? Current school-scoped rows will be replaced.')) return
  actionLoading.value = `restore-${backupId}`
  try {
    await platformApi.restoreSchoolBackup(schoolId.value, backupId)
    toast.success('Backup restored')
    await loadDetails()
    emit('changed')
  } catch (err) {
    toast.error('Could not restore backup', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}

async function deprovision() {
  if (!schoolId.value) return
  if (!window.confirm('Deprovision this school? Access will be blocked and a backup will be created.')) return
  actionLoading.value = 'delete'
  try {
    await platformApi.deleteSchool(schoolId.value, { backup: true })
    toast.success('School deprovisioned')
    open.value = false
    emit('changed')
  } catch (err) {
    toast.error('Could not deprovision school', getErrorMessage(err))
  } finally {
    actionLoading.value = null
  }
}
</script>

<template>
  <Sheet v-model:open="open">
    <SheetContent class="w-full overflow-y-auto sm:max-w-lg" side="right">
      <SheetHeader>
        <SheetTitle>{{ title }}</SheetTitle>
        <SheetDescription>
          Manage lifecycle, custom domains, and tenant backups for this school.
        </SheetDescription>
      </SheetHeader>

      <div v-if="loading" class="flex items-center gap-2 px-4 py-8 text-sm text-muted-foreground">
        <Loader2 class="size-4 animate-spin" aria-hidden="true" />
        Loading school details…
      </div>

      <div v-else class="space-y-8 px-4 pb-8">
        <section class="space-y-3" aria-labelledby="lifecycle-heading">
          <h3 id="lifecycle-heading" class="text-sm font-semibold">Lifecycle</h3>
          <div class="flex flex-wrap items-center gap-2">
            <Badge :variant="schoolStatus === 'active' ? 'default' : 'destructive'">
              {{ schoolStatus }}
            </Badge>
            <Button
              size="sm"
              variant="outline"
              :disabled="!!actionLoading || schoolStatus === 'active'"
              @click="setStatus('active')"
            >
              Activate
            </Button>
            <Button
              size="sm"
              variant="outline"
              :disabled="!!actionLoading || schoolStatus === 'suspended'"
              @click="setStatus('suspended')"
            >
              Suspend
            </Button>
            <Button
              size="sm"
              variant="destructive"
              :disabled="!!actionLoading || schoolStatus === 'deleted'"
              @click="deprovision"
            >
              Deprovision
            </Button>
          </div>
          <dl v-if="usage" class="grid grid-cols-2 gap-2 text-sm">
            <div class="rounded-md border p-3">
              <dt class="text-xs text-muted-foreground">Users</dt>
              <dd class="font-medium">{{ usage.users ?? 0 }}</dd>
            </div>
            <div class="rounded-md border p-3">
              <dt class="text-xs text-muted-foreground">Students</dt>
              <dd class="font-medium">{{ usage.students ?? 0 }}</dd>
            </div>
            <div class="rounded-md border p-3">
              <dt class="text-xs text-muted-foreground">Teachers</dt>
              <dd class="font-medium">{{ usage.teachers ?? 0 }}</dd>
            </div>
            <div class="rounded-md border p-3">
              <dt class="text-xs text-muted-foreground">Invoices</dt>
              <dd class="font-medium">{{ usage.invoices ?? 0 }}</dd>
            </div>
          </dl>
        </section>

        <section class="space-y-3" aria-labelledby="domains-heading">
          <h3 id="domains-heading" class="text-sm font-semibold">Custom domains</h3>
          <form class="flex gap-2" @submit.prevent="addDomain">
            <div class="flex-1 space-y-1">
              <Label for="new-domain" class="sr-only">Domain</Label>
              <Input
                id="new-domain"
                v-model="newDomain"
                placeholder="school.example.com"
                autocomplete="off"
              />
            </div>
            <Button type="submit" size="sm" :disabled="!!actionLoading || !newDomain.trim()">
              <Plus class="size-4" aria-hidden="true" />
              Add
            </Button>
          </form>

          <ul v-if="domains.length" class="space-y-2" aria-label="School domains">
            <li
              v-for="domain in domains"
              :key="String(domain.id)"
              class="rounded-md border p-3 space-y-2"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p class="font-medium">{{ domain.domain }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ domain.is_primary ? 'Primary · ' : '' }}{{ domain.is_verified ? 'Verified' : 'Unverified' }}
                  </p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-if="!domain.is_verified"
                    size="sm"
                    variant="outline"
                    :disabled="!!actionLoading"
                    @click="verifyDomain(Number(domain.id), true)"
                  >
                    Verify
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    :disabled="!!actionLoading"
                    @click="removeDomain(Number(domain.id))"
                  >
                    Remove
                  </Button>
                </div>
              </div>
              <p
                v-if="domain.verification && typeof domain.verification === 'object'"
                class="text-xs text-muted-foreground break-all"
              >
                TXT {{ (domain.verification as Record<string, string>).dns_host }}
                =
                {{ (domain.verification as Record<string, string>).dns_value }}
              </p>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">No custom domains yet.</p>
        </section>

        <section class="space-y-3" aria-labelledby="backups-heading">
          <div class="flex items-center justify-between gap-2">
            <h3 id="backups-heading" class="text-sm font-semibold">Backups</h3>
            <Button size="sm" variant="outline" :disabled="!!actionLoading" @click="createBackup">
              <HardDrive class="size-4" aria-hidden="true" />
              Create backup
            </Button>
          </div>
          <ul v-if="backups.length" class="space-y-2" aria-label="School backups">
            <li
              v-for="backup in backups"
              :key="String(backup.id)"
              class="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3"
            >
              <div>
                <p class="text-sm font-medium">
                  {{ formatDateTime(String(backup.created_at ?? '')) }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ backup.row_count ?? 0 }} rows · {{ backup.table_count ?? 0 }} tables
                </p>
              </div>
              <Button
                size="sm"
                variant="outline"
                :disabled="!!actionLoading"
                @click="restoreBackup(Number(backup.id))"
              >
                Restore
              </Button>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">No backups yet.</p>
        </section>
      </div>
    </SheetContent>
  </Sheet>
</template>
