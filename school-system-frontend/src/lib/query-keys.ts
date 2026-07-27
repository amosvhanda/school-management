export const queryKeys = {
  auth: {
    me: () => ['auth', 'me'] as const,
  },
  dashboard: {
    all: (userId?: number | null) => ['dashboard', userId ?? 'anon'] as const,
    kpis: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'kpis'] as const,
    activity: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'activity'] as const,
    monthly: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'monthly'] as const,
    recent: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'recent'] as const,
    workflows: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'workflows'] as const,
    commandCenter: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'command-center'] as const,
    financeSummary: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'finance-summary'] as const,
    schoolWidgets: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'school-widgets'] as const,
    lmsWidgets: (userId?: number | null) => ['dashboard', userId ?? 'anon', 'lms-widgets'] as const,
    rolePreview: (userId?: number | null, role?: string) =>
      ['dashboard', userId ?? 'anon', 'role-preview', role ?? 'student'] as const,
  },
  parent: {
    unread: (userId?: number | null) => ['parent', userId ?? 'anon', 'notifications-unread'] as const,
  },
  roles: {
    all: () => ['roles'] as const,
    list: (params?: Record<string, unknown>) => ['roles', 'list', params ?? {}] as const,
    detail: (id: number | string) => ['roles', 'detail', id] as const,
  },
} as const
