import { schoolSetupHref } from '@/modules/settings/school-setup-links'

export type SettingsSetupNavItem = {
  label: string
  href: string
  active: boolean
}

/** Inner settings rail shown on Academic Year, Grading, Class, and Exam panes (PreSkool). */
export function buildSettingsSetupNav(activeLabel: string): SettingsSetupNavItem[] {
  const links: Array<{ label: string; href: string }> = [
    { label: 'School Profile', href: schoolSetupHref('profile') },
    { label: 'Academic Year', href: schoolSetupHref('academic-year') },
    { label: 'Class Settings', href: schoolSetupHref('class-settings') },
    { label: 'Subject Settings', href: schoolSetupHref('subjects') },
    { label: 'Grading Rules', href: schoolSetupHref('grading') },
    { label: 'Exam Settings', href: schoolSetupHref('exam-settings') },
    { label: 'Payment Settings', href: '/finance?tab=payment-settings' },
  ]

  return links.map((link) => ({
    ...link,
    active: link.label === activeLabel,
  }))
}
