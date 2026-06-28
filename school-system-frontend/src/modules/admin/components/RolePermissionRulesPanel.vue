<script setup lang="ts">
import { computed, ref } from 'vue'
import { Search } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { groupPermissions, resourceLabel, type PermissionRecord } from '@/modules/admin/types'

const props = defineProps<{
  permissions: PermissionRecord[]
}>()

const search = ref('')

const capabilityLabels: Record<string, string> = {
  isStaff: 'Staff access',
  isParent: 'Parent portal',
  isSuperAdmin: 'Platform admin',
  canManageStudents: 'Manage students',
  canManageTeachers: 'Manage teachers & admin',
  canManageFinance: 'Manage finance',
  canViewAuditLogs: 'View audit trail',
  canManageExaminations: 'Manage exams',
  canEnterExamResults: 'Enter exam marks',
}

const filtered = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return props.permissions
  return props.permissions.filter(
    (p) =>
      p.name.toLowerCase().includes(query)
      || p.slug.toLowerCase().includes(query)
      || p.resource.toLowerCase().includes(query)
      || (p.description ?? '').toLowerCase().includes(query),
  )
})

const grouped = computed(() => groupPermissions(filtered.value))
</script>

<template>
  <div class="space-y-4">
    <!-- Notice Banner -->
    <div class="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground">
      Permission rules define what each grant allows in the app. Assign them to roles on the
      <strong class="font-medium text-foreground">Roles</strong> tab. Users receive updated access after they sign in again.
    </div>

    <!-- Search Input Wrapper -->
    <div class="relative w-full max-w-md">
      <Search
        class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
      <Label for="rules-search" class="sr-only">Search permission rules</Label>
      <Input
        id="rules-search"
        v-model="search"
        type="search"
        placeholder="Search rules…"
        class="h-10 pl-9"
        autocomplete="off"
      />
    </div>

    <!-- Grouped Sections -->
    <div class="space-y-6">
      <section v-for="(items, resource) in grouped" :key="resource" class="space-y-3">
        <h3 class="text-sm font-semibold tracking-tight">{{ resourceLabel(String(resource)) }}</h3>

        <div class="grid gap-3 lg:grid-cols-2">
          <!-- Standard shadcn Card component replace 'surface-card' -->
          <Card
            v-for="rule in items"
            :key="rule.id"
            class="bg-card text-card-foreground shadow-sm"
          >
            <CardContent class="p-4 space-y-2">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <h4 class="text-sm font-medium leading-none">{{ rule.name }}</h4>
                  <p class="font-mono text-[11px] text-muted-foreground mt-1">{{ rule.slug }}</p>
                </div>
                <Badge variant="outline" class="font-normal shrink-0">{{ rule.action }}</Badge>
              </div>

              <p v-if="rule.description" class="text-xs text-muted-foreground leading-relaxed">
                {{ rule.description }}
              </p>

              <div v-if="rule.capabilities?.length" class="flex flex-wrap gap-1.5 pt-1">
                <Badge
                  v-for="cap in rule.capabilities"
                  :key="cap"
                  variant="secondary"
                  class="font-normal text-xs"
                >
                  {{ capabilityLabels[cap] ?? cap }}
                </Badge>
              </div>
              <p v-else class="text-xs text-muted-foreground/70 italic pt-1">
                No mapped UI capability
              </p>
            </CardContent>
          </Card>
        </div>
      </section>
    </div>

    <!-- Empty State -->
    <p v-if="!filtered.length" class="text-sm text-muted-foreground" role="status">
      No rules match your search.
    </p>
  </div>
</template>
