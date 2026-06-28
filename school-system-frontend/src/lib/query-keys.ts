export const queryKeys = {
  auth: {
    me: () => ['auth', 'me'] as const,
  },
  dashboard: {
    all: () => ['dashboard'] as const,
    kpis: () => ['dashboard', 'kpis'] as const,
    activity: () => ['dashboard', 'activity'] as const,
    monthly: () => ['dashboard', 'monthly'] as const,
    recent: () => ['dashboard', 'recent'] as const,
    workflows: () => ['dashboard', 'workflows'] as const,
    commandCenter: () => ['dashboard', 'command-center'] as const,
    financeSummary: () => ['dashboard', 'finance-summary'] as const,
  },
  roles: {
    all: () => ['roles'] as const,
    list: (params?: Record<string, unknown>) => ['roles', 'list', params ?? {}] as const,
    detail: (id: number | string) => ['roles', 'detail', id] as const,
  },
} as const
