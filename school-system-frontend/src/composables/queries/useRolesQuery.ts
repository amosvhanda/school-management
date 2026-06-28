import { useApiQuery } from '@/composables/useApiQuery'
import { queryKeys } from '@/lib/query-keys'
import type { RoleRecord } from '@/modules/admin/types'
import { rolesApi } from '@/services/api.service'
import type { ListQueryParams } from '@/types/api'
import type { MaybeRefOrGetter } from 'vue'
import { computed, toValue } from 'vue'

export function useRolesQuery(params?: MaybeRefOrGetter<ListQueryParams | undefined>) {
  const resolvedParams = computed(() => toValue(params))

  return useApiQuery<RoleRecord[]>(
    computed(() => queryKeys.roles.list(resolvedParams.value)),
    () => rolesApi.list(resolvedParams.value) as Promise<RoleRecord[]>,
    {
      staleTime: 30_000,
    },
  )
}
