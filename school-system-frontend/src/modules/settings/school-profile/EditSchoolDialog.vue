<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

export type SchoolEditPayload = {
  name: string
  principal_name: string
  email: string
  phone: string
  website: string
  year_founded: string
  suburb: string
  city: string
  timezone: string
  motto: string
  address: string
  academic_year: string
  school_type: string
  affiliation_board: string
  medium_of_instruction: string
  legal_name: string
}

const props = defineProps<{
  open: boolean
  saving?: boolean
  initial: Partial<SchoolEditPayload>
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  save: [payload: SchoolEditPayload]
}>()

const form = reactive<SchoolEditPayload>({
  name: '',
  principal_name: '',
  email: '',
  phone: '',
  website: '',
  year_founded: '',
  suburb: '',
  city: '',
  timezone: '',
  motto: '',
  address: '',
  academic_year: '',
  school_type: '',
  affiliation_board: '',
  medium_of_instruction: '',
  legal_name: '',
})

watch(
  () => [props.open, props.initial] as const,
  ([open]) => {
    if (!open) return
    Object.assign(form, {
      name: String(props.initial.name ?? ''),
      principal_name: String(props.initial.principal_name ?? ''),
      email: String(props.initial.email ?? ''),
      phone: String(props.initial.phone ?? ''),
      website: String(props.initial.website ?? ''),
      year_founded: String(props.initial.year_founded ?? ''),
      suburb: String(props.initial.suburb ?? ''),
      city: String(props.initial.city ?? ''),
      timezone: String(props.initial.timezone ?? ''),
      motto: String(props.initial.motto ?? ''),
      address: String(props.initial.address ?? ''),
      academic_year: String(props.initial.academic_year ?? ''),
      school_type: String(props.initial.school_type ?? ''),
      affiliation_board: String(props.initial.affiliation_board ?? ''),
      medium_of_instruction: String(props.initial.medium_of_instruction ?? ''),
      legal_name: String(props.initial.legal_name ?? ''),
    })
  },
  { immediate: true, deep: true },
)

const openModel = computed({
  get: () => props.open,
  set: (v: boolean) => emit('update:open', v),
})

function onSave() {
  emit('save', { ...form })
}
</script>

<template>
  <Dialog v-model:open="openModel">
    <DialogContent class="flex max-h-[90dvh] flex-col gap-0 overflow-hidden p-0 sm:max-w-2xl">
      <DialogHeader class="shrink-0 border-b border-border/60 px-5 py-4 text-left">
        <DialogTitle>Edit School Information</DialogTitle>
        <DialogDescription>
          Update the canonical school record. Changes apply across invoices, reports and portals.
        </DialogDescription>
      </DialogHeader>

      <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2 sm:col-span-2">
            <Label for="edit-school-name">School name</Label>
            <Input id="edit-school-name" v-model="form.name" autocomplete="organization" />
          </div>
          <div class="space-y-2">
            <Label for="edit-legal-name">Legal name</Label>
            <Input id="edit-legal-name" v-model="form.legal_name" />
          </div>
          <div class="space-y-2">
            <Label for="edit-school-type">School type</Label>
            <Input id="edit-school-type" v-model="form.school_type" placeholder="e.g. Co-educational · Day" />
          </div>
          <div class="space-y-2">
            <Label for="edit-board">Affiliation board</Label>
            <Input id="edit-board" v-model="form.affiliation_board" />
          </div>
          <div class="space-y-2">
            <Label for="edit-medium">Medium of instruction</Label>
            <Input id="edit-medium" v-model="form.medium_of_instruction" />
          </div>
          <div class="space-y-2">
            <Label for="edit-principal">Principal</Label>
            <Input id="edit-principal" v-model="form.principal_name" />
          </div>
          <div class="space-y-2">
            <Label for="edit-year">Year founded</Label>
            <Input id="edit-year" v-model="form.year_founded" inputmode="numeric" />
          </div>
          <div class="space-y-2">
            <Label for="edit-academic-year">Academic year</Label>
            <Input id="edit-academic-year" v-model="form.academic_year" placeholder="e.g. 2026 - 2027" />
          </div>
          <div class="space-y-2">
            <Label for="edit-email">Official email</Label>
            <Input id="edit-email" v-model="form.email" type="email" autocomplete="email" />
          </div>
          <div class="space-y-2">
            <Label for="edit-phone">Primary phone</Label>
            <Input id="edit-phone" v-model="form.phone" autocomplete="tel" />
          </div>
          <div class="space-y-2">
            <Label for="edit-website">Website</Label>
            <Input id="edit-website" v-model="form.website" inputmode="url" />
          </div>
          <div class="space-y-2">
            <Label for="edit-timezone">Timezone</Label>
            <Input id="edit-timezone" v-model="form.timezone" placeholder="Africa/Harare" />
          </div>
          <div class="space-y-2">
            <Label for="edit-city">City</Label>
            <Input id="edit-city" v-model="form.city" />
          </div>
          <div class="space-y-2">
            <Label for="edit-suburb">Suburb</Label>
            <Input id="edit-suburb" v-model="form.suburb" />
          </div>
          <div class="space-y-2 sm:col-span-2">
            <Label for="edit-motto">Motto</Label>
            <Input id="edit-motto" v-model="form.motto" />
          </div>
          <div class="space-y-2 sm:col-span-2">
            <Label for="edit-address">Address</Label>
            <Textarea id="edit-address" v-model="form.address" rows="3" />
          </div>
        </div>
      </div>

      <DialogFooter class="shrink-0 border-t border-border/60 px-5 py-4">
        <Button type="button" variant="outline" :disabled="saving" @click="openModel = false">
          Cancel
        </Button>
        <Button type="button" :disabled="saving" @click="onSave">
          {{ saving ? 'Saving…' : 'Save information' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
