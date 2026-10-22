import type { Component } from 'vue'
import {
  Building2,
  FileText,
  GraduationCap,
  History,
  IdCard,
  Languages,
  Lock,
  Mail,
  MapPin,
  Palette,
  Phone,
  Scale,
  Share2,
  Users,
} from '@lucide/vue'

export type SchoolProfileSectionId =
  | 'general'
  | 'identity'
  | 'branding'
  | 'contact'
  | 'address'
  | 'social'
  | 'communication'
  | 'academic'
  | 'campus'
  | 'management'
  | 'compliance'
  | 'documents'
  | 'security'
  | 'audit'

export type SchoolProfileGroupId =
  | 'profile'
  | 'contact'
  | 'academic'
  | 'governance'
  | 'records'

export interface SchoolProfileSection {
  id: SchoolProfileSectionId
  label: string
  icon: Component
  group: SchoolProfileGroupId
  title: string
  subtitle: string
}

export const SCHOOL_PROFILE_GROUPS: Array<{
  id: SchoolProfileGroupId
  label: string
}> = [
  { id: 'profile', label: 'Profile' },
  { id: 'contact', label: 'Contact' },
  { id: 'academic', label: 'Academic' },
  { id: 'governance', label: 'Governance' },
  { id: 'records', label: 'Records' },
]

export const SCHOOL_PROFILE_SECTIONS: SchoolProfileSection[] = [
  {
    id: 'general',
    label: 'General Information',
    icon: Building2,
    group: 'profile',
    title: 'General Information',
    subtitle: 'The canonical record every other module reads from.',
  },
  {
    id: 'identity',
    label: 'School Identity',
    icon: IdCard,
    group: 'profile',
    title: 'School Identity',
    subtitle: 'Legal name, code, motto and how the school identifies itself.',
  },
  {
    id: 'branding',
    label: 'Branding',
    icon: Palette,
    group: 'profile',
    title: 'Branding',
    subtitle: 'Logos, colours and where each is applied.',
  },
  {
    id: 'contact',
    label: 'Contact Information',
    icon: Phone,
    group: 'contact',
    title: 'Contact Information',
    subtitle: 'Numbers and inboxes on the public site, invoices and letters.',
  },
  {
    id: 'address',
    label: 'Address',
    icon: MapPin,
    group: 'contact',
    title: 'Address & Location',
    subtitle: 'Where the trust is registered, where the campus is, and the map pin.',
  },
  {
    id: 'social',
    label: 'Social Media',
    icon: Share2,
    group: 'contact',
    title: 'Social Media',
    subtitle: 'Accounts linked from the public site and prospectus.',
  },
  {
    id: 'communication',
    label: 'Communication',
    icon: Mail,
    group: 'contact',
    title: 'Communication',
    subtitle: 'How the school identifies itself on every message it sends.',
  },
  {
    id: 'academic',
    label: 'Academic Details',
    icon: GraduationCap,
    group: 'academic',
    title: 'Academic Details',
    subtitle: 'Structural settings that shape timetables, terms, reports and assessment.',
  },
  {
    id: 'campus',
    label: 'Campus & Languages',
    icon: Languages,
    group: 'academic',
    title: 'Campus, Languages & Time',
    subtitle: 'Physical and regional settings used across the platform.',
  },
  {
    id: 'management',
    label: 'Management',
    icon: Users,
    group: 'governance',
    title: 'Management',
    subtitle: 'People whose names appear on issued documents and correspondence.',
  },
  {
    id: 'compliance',
    label: 'Affiliation & Compliance',
    icon: Scale,
    group: 'governance',
    title: 'Affiliation & Compliance',
    subtitle: 'Registrations and licences, with status by how close each is to expiry.',
  },
  {
    id: 'documents',
    label: 'Documents',
    icon: FileText,
    group: 'records',
    title: 'Documents',
    subtitle: 'Everything on file for the institution. Certificates referenced in Compliance.',
  },
  {
    id: 'security',
    label: 'Security',
    icon: Lock,
    group: 'records',
    title: 'Security',
    subtitle: 'Access control, retention and the last review of this profile.',
  },
  {
    id: 'audit',
    label: 'Audit History',
    icon: History,
    group: 'records',
    title: 'Audit History',
    subtitle: 'Changes to this profile, when they happened, and what is known.',
  },
]

export function parseProfileSection(value: unknown): SchoolProfileSectionId {
  const raw = String(Array.isArray(value) ? value[0] : value ?? '')
  const match = SCHOOL_PROFILE_SECTIONS.find((s) => s.id === raw)
  return match?.id ?? 'general'
}

export function sectionById(id: SchoolProfileSectionId): SchoolProfileSection {
  return SCHOOL_PROFILE_SECTIONS.find((s) => s.id === id) ?? SCHOOL_PROFILE_SECTIONS[0]!
}

export function displayValue(value: unknown, fallback = 'Not set'): string {
  if (value == null) return fallback
  const text = String(value).trim()
  return text.length ? text : fallback
}
