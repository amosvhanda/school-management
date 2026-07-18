<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import ListPage from './ListPage.vue'
import CrudListPage from './CrudListPage.vue'
import { listPageRegistry } from './registry'
import { moduleCrudRegistry } from './registry-crud'
import { resolveCrudAccess } from './registry-permissions'
import { moduleActionsRegistry, moduleToolbarActionsRegistry } from './registry-actions'

const route = useRoute()
const { user } = useAuth()

const listKey = computed(() => route.meta.listKey as string)
const config = computed(() => listPageRegistry[listKey.value])
const crud = computed(() => moduleCrudRegistry[listKey.value])
const rowActions = computed(() => moduleActionsRegistry[listKey.value])
const toolbarActions = computed(() => moduleToolbarActionsRegistry[listKey.value])

const crudAccess = computed(() => {
  if (!crud.value) {
    return { canCreate: false, canEdit: false, canDelete: false }
  }
  return resolveCrudAccess(user.value, listKey.value, crud.value)
})
</script>

<template>
  <CrudListPage
    v-if="config && crud"
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
    :row-actions="rowActions"
    :toolbar-actions="toolbarActions"
    :list-key="listKey"
    :staged="crud.staged"
  />
  <ListPage
    v-else-if="config"
    :title="config.title"
    :description="config.description"
    :endpoint="config.endpoint"
    :columns="config.columns"
    :row-actions="rowActions"
    :list-key="listKey"
  />
  <div v-else class="text-destructive">Module configuration not found.</div>
</template>
