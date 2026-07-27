<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { Mail, Phone, Undo2 } from '@lucide/vue'
import EntityDetailShell from '@/components/detail/EntityDetailShell.vue'
import FormSheet from '@/components/forms/FormSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { getErrorMessage } from '@/lib/api-response'
import { formatDate } from '@/lib/format'
import { preloadRelationFields } from '@/lib/relation-options'
import { mapFormToPayload, mapRowToFormValues } from '@/modules/shared/crud-mappers'
import { moduleCrudRegistry } from '@/modules/shared/registry-crud'
import { operationsApi } from '@/services/api.service'
import { endpoints } from '@/services/endpoints'
import { fetchList } from '@/services/dashboard.service'

interface LibraryMember {
  id: number
  member_type?: string
  member_number?: string | null
  name?: string
  email?: string | null
  phone?: string | null
  status?: string
  joined_on?: string | null
  notes?: string | null
}

interface LibraryLoan {
  id: number
  book_id: number
  borrowed_at?: string | null
  due_at?: string | null
  returned_at?: string | null
  status?: string
  fine_amount?: number | null
  book?: { id: number; title?: string; isbn?: string | null } | null
}

const route = useRoute()
const toast = useToast()
const { checkCapability } = useAuth()
const canEdit = computed(() => checkCapability('canManageLibrary'))

const memberConfig = moduleCrudRegistry['ops-library-members']

const id = String(route.params.id)
const loading = ref(true)
const error = ref<string | null>(null)
const member = ref<LibraryMember | null>(null)
const loans = ref<LibraryLoan[]>([])
const returningId = ref<number | null>(null)

const editOpen = ref(false)
const saving = ref(false)
const formLoading = ref(false)
const resetValues = ref<Record<string, unknown> | undefined>()
const formSheetRef = ref<{ applyServerErrors: (error: unknown) => void } | null>(null)

const displayName = computed(() => member.value?.name ?? 'Library member')

const initials = computed(() =>
  displayName.value
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase() || 'L',
)

const subtitle = computed(() => (member.value?.member_number ? `Member #${member.value.member_number}` : undefined))

async function load() {
  loading.value = true
  error.value = null
  try {
    const [memberData, loansData] = await Promise.all([
      operationsApi.library.members.get(id) as Promise<LibraryMember>,
      fetchList<LibraryLoan>(endpoints.library.loans, { library_member_id: id }),
    ])
    member.value = memberData
    loans.value = loansData
  } catch (err) {
    error.value = getErrorMessage(err, 'Failed to load library member')
  } finally {
    loading.value = false
  }
}

async function returnLoan(loan: LibraryLoan) {
  returningId.value = loan.id
  try {
    await operationsApi.library.returnBook(loan.id)
    toast.success('Book returned')
    await load()
  } catch (err) {
    toast.error('Return failed', getErrorMessage(err))
  } finally {
    returningId.value = null
  }
}

function loanStatusVariant(status?: string): 'default' | 'secondary' | 'destructive' | 'outline' {
  if (status === 'returned') return 'default'
  if (status === 'borrowed') return 'secondary'
  if (status === 'overdue') return 'destructive'
  return 'outline'
}

async function openEdit() {
  if (!member.value) return
  resetValues.value = mapRowToFormValues('ops-library-members', member.value as Record<string, unknown>, memberConfig.formFields)
  editOpen.value = true
  formLoading.value = true
  await nextTick()
  try {
    await preloadRelationFields(memberConfig.formFields)
  } finally {
    formLoading.value = false
  }
}

async function onSave(values: Record<string, unknown>) {
  saving.value = true
  try {
    const payload = mapFormToPayload('ops-library-members', values, memberConfig.formFields)
    member.value = await operationsApi.library.members.update(id, payload) as LibraryMember
    toast.success('Library member updated')
    editOpen.value = false
  } catch (err) {
    formSheetRef.value?.applyServerErrors(err)
    toast.error('Update failed', getErrorMessage(err))
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <EntityDetailShell
    :title="displayName"
    :subtitle="subtitle"
    back-to="/operations/library/members"
    back-label="Back to library members"
    :loading="loading"
    :error="error"
    :initials="initials"
    :status="member?.status"
    @retry="load"
  >
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" @click="openEdit">
        Edit member
      </Button>
    </template>

    <div v-if="member" class="grid gap-4 lg:grid-cols-3">
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Profile</CardTitle>
          <CardDescription>Contact and membership details</CardDescription>
        </CardHeader>
        <CardContent>
          <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Member type</dt>
              <dd class="mt-1 text-sm capitalize">{{ member.member_type ?? '—' }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Joined on</dt>
              <dd class="mt-1 text-sm">{{ formatDate(member.joined_on) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Mail class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                <span class="truncate">{{ member.email ?? '—' }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Phone</dt>
              <dd class="mt-1 flex items-center gap-1.5 text-sm">
                <Phone class="size-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                {{ member.phone ?? '—' }}
              </dd>
            </div>
            <div v-if="member.notes" class="sm:col-span-2">
              <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Notes</dt>
              <dd class="mt-1 text-sm">{{ member.notes }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Loan summary</CardTitle>
          <CardDescription>{{ loans.length }} loan{{ loans.length === 1 ? '' : 's' }} on record</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-sm text-muted-foreground">
            {{ loans.filter((l) => l.status === 'borrowed').length }} book(s) currently borrowed.
          </p>
        </CardContent>
      </Card>
    </div>

    <Card v-if="member">
      <CardHeader>
        <CardTitle>Loan history</CardTitle>
        <CardDescription>Books borrowed by this member</CardDescription>
      </CardHeader>
      <CardContent class="p-0">
        <Table v-if="loans.length">
          <TableHeader>
            <TableRow>
              <TableHead>Book</TableHead>
              <TableHead>Borrowed</TableHead>
              <TableHead>Due</TableHead>
              <TableHead>Returned</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Fine</TableHead>
              <TableHead class="text-right">Action</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="loan in loans" :key="loan.id">
              <TableCell class="font-medium">{{ loan.book?.title ?? `Book #${loan.book_id}` }}</TableCell>
              <TableCell>{{ formatDate(loan.borrowed_at) }}</TableCell>
              <TableCell>{{ formatDate(loan.due_at) }}</TableCell>
              <TableCell>{{ formatDate(loan.returned_at) }}</TableCell>
              <TableCell>
                <Badge :variant="loanStatusVariant(loan.status)" class="capitalize">{{ loan.status ?? '—' }}</Badge>
              </TableCell>
              <TableCell class="tabular-nums">
                {{ loan.fine_amount ? Number(loan.fine_amount).toFixed(2) : '—' }}
              </TableCell>
              <TableCell class="text-right">
                <Button
                  v-if="loan.status === 'borrowed' && canEdit"
                  variant="outline"
                  size="sm"
                  :disabled="returningId === loan.id"
                  @click="returnLoan(loan)"
                >
                  <Undo2 class="mr-1 size-3.5" aria-hidden="true" />
                  {{ returningId === loan.id ? 'Returning…' : 'Return' }}
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
        <p v-else class="p-6 text-sm text-muted-foreground">No loans recorded for this member yet.</p>
      </CardContent>
    </Card>
  </EntityDetailShell>

  <FormSheet
    ref="formSheetRef"
    v-model:open="editOpen"
    title="Edit library member"
    description="Update contact and membership details."
    :fields="memberConfig.formFields"
    :schema="memberConfig.formSchema"
    :reset-values="resetValues"
    :form-key="`library-member-edit-${id}`"
    :form-loading="formLoading"
    :saving="saving"
    save-label="Save changes"
    @submit="onSave"
  />
</template>
