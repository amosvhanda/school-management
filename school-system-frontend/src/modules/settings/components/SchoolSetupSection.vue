<script setup lang="ts">
import { computed } from 'vue'
import CrudListPage from '@/modules/shared/CrudListPage.vue'
import { useAuth } from '@/composables/useAuth'
import { listPageRegistry } from '@/modules/shared/registry'
import { moduleCrudRegistry } from '@/modules/shared/registry-crud'
import { resolveCrudAccess } from '@/modules/shared/registry-permissions'
import { moduleActionsRegistry, moduleToolbarActionsRegistry } from '@/modules/shared/registry-actions'

const props = defineProps<{
  listKey: string
  /** When embedded in School Setup, parent controls ?create=1 handling. */
  autoCreate?: boolean
}>()

const emit = defineEmits<{ saved: [] }>()

const { user } = useAuth()

const config = computed(() => listPageRegistry[props.listKey])
const crud = computed(() => moduleCrudRegistry[props.listKey])
const crudAccess = computed(() => {
  if (!crud.value) {
    return { canCreate: false, canEdit: false, canDelete: false }
  }
  return resolveCrudAccess(user.value, props.listKey, crud.value)
})
</script>

<template>
  <CrudListPage
    v-if="config && crud"
    embedded
    :title="config.title"
    :description="config.description"
    :endpoint="config.endpoint"
    :create-endpoint="config.createEndpoint"
    :columns="config.columns"
    :form-fields="crud.formFields"
    :form-schema="crud.formSchema"
    :can-create="crudAccess.canCreate"
    :can-edit="crudAccess.canEdit"
    :can-delete="crudAccess.canDelete"
    :id-key="crud.idKey"
    :row-actions="moduleActionsRegistry[listKey]"
    :toolbar-actions="moduleToolbarActionsRegistry[listKey]"
    :list-key="listKey"
    :staged="crud.staged"
    :auto-create="autoCreate"
    @saved="emit('saved')"
  />
  <p v-else class="text-sm text-destructive">This setup section is not configured.</p>
</template>
