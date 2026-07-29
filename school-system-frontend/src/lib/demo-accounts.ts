/**
 * Demo credentials for local/testing only.
 * Seeded by database/seeders/UserSeeder.php — never enabled in production builds
 * (LoginView only renders these when import.meta.env.DEV is true).
 *
 * Run: php artisan db:seed --class=UserSeeder
 */
export interface DemoAccount {
  label: string
  role: string
  email: string
  password: string
  /** Whether this app allows sign-in for this role */
  webAccess: boolean
  /** Where the user lands after login */
  destination?: string
}

export const DEMO_ACCOUNTS: DemoAccount[] = [
  {
    label: 'Admin',
    role: 'admin',
    email: 'admin@school.co.zw',
    password: 'admin123',
    webAccess: true,
    destination: 'Staff dashboard',
  },
  {
    label: 'Teacher',
    role: 'teacher',
    email: 'teacher@school.co.zw',
    password: 'teacher123',
    webAccess: true,
    destination: 'Staff dashboard',
  },
  {
    label: 'Finance',
    role: 'finance',
    email: 'finance@school.co.zw',
    password: 'finance123',
    webAccess: true,
    destination: 'Finance modules',
  },
  {
    label: 'Accounts',
    role: 'accounts',
    email: 'accounts@school.co.zw',
    password: 'accounts123',
    webAccess: true,
    destination: 'Finance & audit',
  },
  {
    label: 'Exam officer',
    role: 'examination_officer',
    email: 'exam@school.co.zw',
    password: 'exam123',
    webAccess: true,
    destination: 'Exams & gradebook',
  },
  {
    label: 'Parent',
    role: 'parent',
    email: 'parent@school.co.zw',
    password: 'parent123',
    webAccess: true,
    destination: 'Parent portal',
  },
  {
    label: 'Super admin',
    role: 'super_admin',
    email: 'super@school.co.zw',
    password: 'super123',
    webAccess: true,
    destination: 'Super admin portal',
  },
  {
    label: 'Student',
    role: 'student',
    email: 'student@school.co.zw',
    password: 'student123',
    webAccess: true,
    destination: 'Student portal',
  },
]

export const WEB_DEMO_ACCOUNTS = DEMO_ACCOUNTS.filter((a) => a.webAccess)
