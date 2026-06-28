import { useMutation, useQuery, useQueryClient, type QueryKey, type UseMutationOptions, type UseQueryOptions } from '@tanstack/vue-query'
import { computed, toValue, type MaybeRefOrGetter } from 'vue'

type QueryFn<T> = () => Promise<T>

export function useApiQuery<TData>(
  queryKey: MaybeRefOrGetter<QueryKey>,
  queryFn: QueryFn<TData>,
  options?: Omit<UseQueryOptions<TData, Error, TData, QueryKey>, 'queryKey' | 'queryFn'>,
) {
  return useQuery({
    queryKey: computed(() => toValue(queryKey)),
    queryFn,
    ...options,
  })
}

export function useApiMutation<TData, TVariables>(
  mutationFn: (variables: TVariables) => Promise<TData>,
  options?: UseMutationOptions<TData, Error, TVariables>,
) {
  return useMutation({
    mutationFn,
    ...options,
  })
}

export function useInvalidateQueries() {
  const queryClient = useQueryClient()

  return {
    invalidate: (queryKey: QueryKey) => queryClient.invalidateQueries({ queryKey }),
    remove: (queryKey: QueryKey) => queryClient.removeQueries({ queryKey }),
    clear: () => queryClient.clear(),
  }
}

export function useEnabledApiQuery<TData>(
  queryKey: MaybeRefOrGetter<QueryKey>,
  queryFn: QueryFn<TData>,
  enabled: MaybeRefOrGetter<boolean>,
  options?: Omit<UseQueryOptions<TData, Error, TData, QueryKey>, 'queryKey' | 'queryFn' | 'enabled'>,
) {
  return useApiQuery(queryKey, queryFn, {
    ...options,
    enabled: () => toValue(enabled),
  })
}
