<script setup lang="ts">
import { computed } from 'vue'
import { useAuth } from '@/composables/useAuth'
import CrudListPage from '@/modules/shared/CrudListPage.vue'
import { contactMessageColumns } from '@/modules/shared/columns'
import { moduleCrudRegistry } from '@/modules/shared/registry-crud'
import { moduleActionsRegistry } from '@/modules/shared/registry-actions'
import { resolveCrudAccess } from '@/modules/shared/registry-permissions'
import { moduleEndpoints } from '@/services'

const LIST_KEY = 'ops-help-desk'

const { user } = useAuth()
const crud = moduleCrudRegistry[LIST_KEY]
const rowActions = moduleActionsRegistry[LIST_KEY]

const access = computed(() =>
  crud ? resolveCrudAccess(user.value, LIST_KEY, crud) : { canCreate: false, canEdit: false, canDelete: false },
)
</script>

<template>
  <CrudListPage
    v-if="crud"
    title="Contact Messages"
    description="Inbound enquiries from parents, visitors, and the public — track and respond from one list."
    :breadcrumbs="['Support', 'Contact Messages']"
    :endpoint="moduleEndpoints.helpDeskTickets"
    :columns="contactMessageColumns"
    :form-fields="crud.formFields"
    :form-schema="crud.formSchema"
    :can-create="access.canCreate"
    :can-edit="access.canEdit"
    :can-delete="access.canDelete"
    :id-key="crud.idKey"
    :row-actions="rowActions"
    :list-key="LIST_KEY"
    :staged="crud.staged"
  />
</template>
