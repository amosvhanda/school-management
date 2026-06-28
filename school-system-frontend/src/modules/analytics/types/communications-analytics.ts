export interface CommunicationThreadRow {
  id: number
  subject?: string
  status?: string
  last_message_at?: string
  staff_user_id?: number | null
  student?: { full_name?: string }
  parent?: { name?: string; email?: string }
  staff?: { name?: string }
}

export interface AnnouncementRow {
  id: number
  title?: string
  message?: string
  type?: string
  target_audience?: string
  audience?: string
  date?: string
  is_active?: boolean
  created_at?: string
}

export interface CommunicationsDetails {
  threads: CommunicationThreadRow[]
  announcements: AnnouncementRow[]
  unassignedThreads: number
  openThreads: number
  activeAnnouncements: number
}
