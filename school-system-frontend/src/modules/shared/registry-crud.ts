import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { PAYMENT_METHOD_OPTIONS } from '@/lib/finance-constants'
import {
  inventoryItemRelation,
  invoiceRelation,
  payrollTeacherRelation,
  studentRelation,
  transportDriverRelation,
  transportVehicleRelation,
  classRelation,
} from '@/lib/form-relations'
import { moduleEndpoints } from '@/services'
import { studentFormFields, studentFormSchema } from '@/modules/students/student-form'
import { guardianFormFields, guardianFormSchema } from '@/modules/guardians/guardian-form'
import { leaveFormFields, leaveFormSchema } from '@/modules/hr/leave-form'
import { teacherFormFields, teacherFormSchema } from '@/modules/teachers/teacher-form'

export interface ModuleCrudConfig {
  formFields: FormFieldSchema[]
  formSchema: z.ZodTypeAny
  canCreate?: boolean
  canEdit?: boolean
  canDelete?: boolean
  idKey?: string
  /** Show one section per step until submit (for long multi-section forms). */
  staged?: boolean
}

function crud(
  formFields: FormFieldSchema[],
  formSchema: z.ZodTypeAny,
  options: Partial<ModuleCrudConfig> = {},
): ModuleCrudConfig {
  return {
    canCreate: true,
    canEdit: true,
    canDelete: true,
    formFields,
    formSchema,
    ...options,
  }
}

const statusOptions = [
  { label: 'Present', value: 'present' },
  { label: 'Absent', value: 'absent' },
  { label: 'Late', value: 'late' },
  { label: 'Excused', value: 'excused' },
]

export const moduleCrudRegistry: Record<string, ModuleCrudConfig> = {
  students: crud(studentFormFields, studentFormSchema, { staged: true }),
  teachers: crud(teacherFormFields, teacherFormSchema),
  guardians: crud(guardianFormFields, guardianFormSchema, { canEdit: true, canDelete: false }),
  'academics-setup': crud(
    [
      { name: 'name', label: 'Class name', type: 'text', required: true, section: 'Class details', colSpan: 1 },
      { name: 'form', label: 'Form / level label', type: 'text', section: 'Class details', placeholder: 'Form 1', colSpan: 1 },
      { name: 'capacity', label: 'Capacity', type: 'number', section: 'Class details', colSpan: 1 },
      {
        name: 'teacher_id',
        label: 'Class teacher',
        type: 'relation',
        section: 'Class details',
        placeholder: 'Select teacher',
        description: 'Optional. Assign the homeroom / class teacher.',
        colSpan: 1,
        relation: { endpoint: moduleEndpoints.teachers },
      },
    ],
    z.object({
      name: z.string().min(1),
      form: z.string().optional(),
      capacity: z.coerce.number().optional(),
      teacher_id: z.string().optional().or(z.literal('')),
    }),
  ),
  'academics-subjects': crud(
    [
      { name: 'name', label: 'Subject name', type: 'text', required: true, section: 'Subject', colSpan: 1 },
      { name: 'code', label: 'Code', type: 'text', section: 'Subject', colSpan: 1 },
    ],
    z.object({
      name: z.string().min(1),
      code: z.string().optional(),
    }),
  ),
  'academics-departments': crud(
    [
      { name: 'name', label: 'Department name', type: 'text', required: true, section: 'Department', colSpan: 2 },
      {
        name: 'head_teacher_id',
        label: 'Department head',
        type: 'relation',
        section: 'Department',
        placeholder: 'Select teacher',
        colSpan: 2,
        relation: { endpoint: moduleEndpoints.teachers },
      },
    ],
    z.object({
      name: z.string().min(1),
      head_teacher_id: z.string().optional().or(z.literal('')),
    }),
  ),
  'academics-grade-levels': crud(
    formSection('Grade level', [
      { name: 'name', label: 'Grade level', type: 'text', required: true },
      { name: 'order', label: 'Display order', type: 'number', description: 'Lower numbers appear first.' },
    ]),
    z.object({ name: z.string().min(1), order: z.coerce.number().optional() }),
  ),
  'academics-grading-scales': crud(
    formSection('Grading scale', [
      { name: 'name', label: 'Scale name', type: 'text', required: true },
      { name: 'min_score', label: 'Min score', type: 'number', required: true },
      { name: 'max_score', label: 'Max score', type: 'number', required: true },
      { name: 'grade', label: 'Grade letter', type: 'text', required: true },
    ]),
    z.object({
      name: z.string().min(1),
      min_score: z.coerce.number(),
      max_score: z.coerce.number(),
      grade: z.string().min(1),
    }),
  ),
  'academics-rooms': crud(
    formSection('Room details', [
      { name: 'name', label: 'Room name', type: 'text', required: true },
      { name: 'capacity', label: 'Capacity', type: 'number' },
      { name: 'building', label: 'Building', type: 'text' },
    ]),
    z.object({ name: z.string().min(1), capacity: z.coerce.number().optional(), building: z.string().optional() }),
  ),
  'academics-terms': crud(
    formSection('Term details', [
      { name: 'name', label: 'Term name', type: 'text', required: true },
      { name: 'start_date', label: 'Start date', type: 'date', required: true },
      { name: 'end_date', label: 'End date', type: 'date', required: true },
      { name: 'academic_year', label: 'Academic year', type: 'text', required: true, placeholder: '2026' },
    ]),
    z.object({
      name: z.string().min(1),
      start_date: z.string().min(1),
      end_date: z.string().min(1),
      academic_year: z.string().min(1),
    }),
  ),
  'academics-assignments': crud(
    formSection('Assignment', [
      { name: 'title', label: 'Title', type: 'text', required: true, colSpan: 2 },
      {
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        required: true,
        placeholder: 'Select class',
        relation: classRelation(),
      },
      {
        name: 'subject_id',
        label: 'Subject',
        type: 'relation',
        required: true,
        placeholder: 'Select subject',
        relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
      },
      {
        name: 'teacher_id',
        label: 'Teacher',
        type: 'relation',
        required: true,
        placeholder: 'Select teacher',
        relation: { endpoint: moduleEndpoints.teachers },
      },
      { name: 'due_date', label: 'Due date', type: 'date', required: true },
    ]),
    z.object({
      title: z.string().min(1),
      class_id: z.string().min(1),
      subject_id: z.string().min(1),
      teacher_id: z.string().min(1),
      due_date: z.string().min(1),
    }),
  ),
  'academics-tests': crud(
    formSection('Test details', [
      { name: 'name', label: 'Test name', type: 'text', required: true, colSpan: 2 },
      {
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        required: true,
        placeholder: 'Select class',
        relation: classRelation(),
      },
      {
        name: 'subject_id',
        label: 'Subject',
        type: 'relation',
        required: true,
        placeholder: 'Select subject',
        relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
      },
      { name: 'test_date', label: 'Test date', type: 'date' },
    ]),
    z.object({
      name: z.string().min(1),
      class_id: z.string().min(1),
      subject_id: z.string().min(1),
      test_date: z.string().optional().or(z.literal('')),
    }),
  ),
  'academics-teacher-assignments': crud(
    formSection('Assignment', [
      {
        name: 'teacher_id',
        label: 'Teacher',
        type: 'relation',
        required: true,
        placeholder: 'Select teacher',
        relation: { endpoint: moduleEndpoints.teachers },
      },
      {
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        required: true,
        placeholder: 'Select class',
        relation: classRelation(),
      },
      {
        name: 'subject_id',
        label: 'Subject',
        type: 'relation',
        required: true,
        placeholder: 'Select subject',
        relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
      },
    ]),
    z.object({
      teacher_id: z.string().min(1),
      class_id: z.string().min(1),
      subject_id: z.string().min(1),
    }),
  ),
  'academics-attendance': crud(
    formSection('Attendance', [
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        required: true,
        placeholder: 'Select student',
        relation: studentRelation(),
      },
      {
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        placeholder: 'Select class',
        relation: classRelation(),
      },
      { name: 'date', label: 'Date', type: 'date', required: true },
      { name: 'status', label: 'Status', type: 'select', required: true, options: statusOptions },
    ]),
    z.object({
      student_id: z.string().min(1),
      class_id: z.string().optional().or(z.literal('')),
      date: z.string().min(1),
      status: z.string().min(1),
    }),
    { canDelete: false },
  ),
  'academics-timetable': crud(
    [
      {
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        required: true,
        placeholder: 'Select class',
        relation: { endpoint: moduleEndpoints.classes, moduleLabel: 'class' },
      },
      {
        name: 'subject_id',
        label: 'Subject',
        type: 'relation',
        required: true,
        placeholder: 'Select subject',
        relation: { endpoint: moduleEndpoints.subjects, moduleLabel: 'subject' },
      },
      { name: 'day', label: 'Day', type: 'text', required: true, placeholder: 'Monday' },
      { name: 'start_time', label: 'Start', type: 'text', required: true, placeholder: '08:00' },
      { name: 'end_time', label: 'End', type: 'text', required: true, placeholder: '09:00' },
    ],
    z.object({
      class_id: z.string().min(1),
      subject_id: z.string().min(1),
      day: z.string().min(1),
      start_time: z.string().min(1),
      end_time: z.string().min(1),
    }),
  ),
  'academics-holiday-programs': crud(
    formSection('Program details', [
      { name: 'name', label: 'Program name', type: 'text', required: true },
      { name: 'start_date', label: 'Start date', type: 'date', required: true },
      { name: 'end_date', label: 'End date', type: 'date', required: true },
      { name: 'fee_amount', label: 'Fee', type: 'number', placeholder: '0.00' },
    ]),
    z.object({
      name: z.string().min(1),
      start_date: z.string().min(1),
      end_date: z.string().min(1),
      fee_amount: z.coerce.number().optional(),
    }),
    { canDelete: false },
  ),
  'finance-payments': crud(
    [
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        required: true,
        placeholder: 'Select student',
        colSpan: 2,
        relation: studentRelation(),
      },
      {
        name: 'invoice_id',
        label: 'Invoice',
        type: 'relation',
        required: true,
        placeholder: 'Select unpaid invoice',
        colSpan: 2,
        relation: invoiceRelation({
          dependsOn: { field: 'student_id', paramKey: 'student_id' },
        }),
      },
      { name: 'amount', label: 'Amount', type: 'number', required: true, placeholder: '0.00' },
      {
        name: 'method',
        label: 'Payment method',
        type: 'select',
        required: true,
        options: [...PAYMENT_METHOD_OPTIONS],
      },
      { name: 'reference', label: 'Reference / receipt no.', type: 'text', placeholder: 'Optional transaction reference' },
      { name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'Optional internal note' },
    ],
    z.object({
      student_id: z.string().min(1, 'Select a student'),
      invoice_id: z.string().min(1, 'Select an invoice'),
      amount: z.coerce.number().positive('Amount must be greater than zero'),
      method: z.string().min(1, 'Select a payment method'),
      reference: z.string().optional(),
      notes: z.string().optional(),
    }),
    { canEdit: false, canDelete: false },
  ),
  'finance-invoices': crud(
    [
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        required: true,
        placeholder: 'Select student',
        colSpan: 2,
        relation: studentRelation(),
      },
      { name: 'description', label: 'Description', type: 'text', required: true, placeholder: 'Term 1 tuition, exam fees…', colSpan: 2 },
      { name: 'amount', label: 'Amount', type: 'number', required: true },
      { name: 'due_date', label: 'Due date', type: 'date', required: true },
    ],
    z.object({
      student_id: z.string().min(1, 'Select a student'),
      description: z.string().min(1, 'Description is required'),
      amount: z.coerce.number().positive('Amount must be greater than zero'),
      due_date: z.string().min(1, 'Due date is required'),
    }),
    { canDelete: false },
  ),
  'finance-fees': crud(
    [
      {
        name: 'class',
        label: 'Class',
        type: 'text',
        required: true,
        placeholder: 'Form 1A',
        description: 'Class name this fee applies to.',
        rowKey: 'class_name',
      },
      {
        name: 'category',
        label: 'Category',
        type: 'text',
        required: true,
        placeholder: 'Tuition, levies, transport…',
      },
      { name: 'amount', label: 'Amount', type: 'number', required: true },
      {
        name: 'currency',
        label: 'Currency',
        type: 'select',
        required: true,
        options: [
          { label: 'USD', value: 'USD' },
          { label: 'ZWL', value: 'ZWL' },
        ],
      },
    ],
    z.object({
      class: z.string().min(1, 'Class is required'),
      category: z.string().min(1, 'Category is required'),
      amount: z.coerce.number().positive('Amount must be greater than zero'),
      currency: z.enum(['USD', 'ZWL']),
    }),
  ),
  'finance-fee-categories': crud(
    [
      { name: 'name', label: 'Category name', type: 'text', required: true },
      { name: 'description', label: 'Description', type: 'textarea' },
    ],
    z.object({ name: z.string().min(1), description: z.string().optional() }),
  ),
  'finance-transactions': crud([], z.object({}), { canCreate: false, canEdit: false, canDelete: false }),
  'finance-payroll': crud(
    formSection('Payroll adjustment', [
      {
        name: 'employee_id',
        label: 'Staff member',
        type: 'relation',
        required: true,
        placeholder: 'Select staff member',
        colSpan: 2,
        relation: payrollTeacherRelation(),
      },
      { name: 'gross_salary', label: 'Gross salary', type: 'number', required: true },
      { name: 'month', label: 'Month', type: 'number', required: true, placeholder: '1–12' },
      { name: 'year', label: 'Year', type: 'number', required: true, placeholder: '2026' },
    ]),
    z.object({
      employee_id: z.string().min(1, 'Select a staff member'),
      gross_salary: z.coerce.number().min(0),
      month: z.coerce.number().min(1).max(12),
      year: z.coerce.number().min(2000),
    }),
    { canCreate: false, canDelete: false },
  ),
  'ops-inventory': crud(
    [
      { name: 'name', label: 'Item name', type: 'text', required: true },
      { name: 'sku', label: 'SKU', type: 'text' },
      { name: 'stock_quantity', label: 'Stock quantity', type: 'number', required: true },
      { name: 'unit_price', label: 'Unit price', type: 'number', required: true },
    ],
    z.object({
      name: z.string().min(1),
      sku: z.string().optional(),
      stock_quantity: z.coerce.number().min(0),
      unit_price: z.coerce.number().min(0),
    }),
    { canDelete: false },
  ),
  'ops-inventory-sales': crud(
    formSection('Sale details', [
      {
        name: 'item_id',
        label: 'Inventory item',
        type: 'relation',
        required: true,
        placeholder: 'Select item',
        relation: inventoryItemRelation(),
      },
      { name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: '1' },
      {
        name: 'payment_method',
        label: 'Payment method',
        type: 'select',
        required: true,
        options: [
          { label: 'Cash', value: 'cash' },
          { label: 'Student account', value: 'student_account' },
          { label: 'Upfront', value: 'upfront' },
        ],
      },
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        placeholder: 'Optional — link to student',
        colSpan: 2,
        relation: studentRelation(),
      },
    ]),
    z.object({
      item_id: z.string().min(1, 'Select an inventory item'),
      quantity: z.coerce.number().min(1),
      payment_method: z.enum(['cash', 'student_account', 'upfront']),
      student_id: z.string().optional().or(z.literal('')),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-procurement': crud(
    formSection('Requisition', [
      { name: 'title', label: 'Title', type: 'text', required: true, colSpan: 2 },
      {
        name: 'department_id',
        label: 'Department',
        type: 'relation',
        placeholder: 'Select department',
        colSpan: 2,
        relation: { endpoint: moduleEndpoints.departments },
      },
      { name: 'item_description', label: 'Item description', type: 'text', required: true, colSpan: 2 },
      { name: 'item_quantity', label: 'Quantity', type: 'number', required: true, placeholder: '1' },
      { name: 'item_unit_cost', label: 'Unit cost', type: 'number', required: true, placeholder: '0.00' },
      {
        name: 'submit',
        label: 'Submit for approval',
        type: 'checkbox',
        description: 'Start the purchase approval workflow immediately.',
        colSpan: 2,
      },
    ]),
    z.object({
      title: z.string().min(1),
      department_id: z.string().optional().or(z.literal('')),
      item_description: z.string().min(1, 'Item description is required'),
      item_quantity: z.coerce.number().min(1),
      item_unit_cost: z.coerce.number().min(0),
      submit: z.boolean().optional(),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-procurement-vendors': crud(
    [
      { name: 'name', label: 'Vendor name', type: 'text', required: true },
      { name: 'contact_person', label: 'Contact person', type: 'text' },
      { name: 'phone', label: 'Phone', type: 'text' },
    ],
    z.object({ name: z.string().min(1), contact_person: z.string().optional(), phone: z.string().optional() }),
    { canEdit: false, canDelete: false },
  ),
  'ops-library': crud(
    [
      { name: 'title', label: 'Title', type: 'text', required: true },
      { name: 'author', label: 'Author', type: 'text' },
      { name: 'isbn', label: 'ISBN', type: 'text' },
    ],
    z.object({ title: z.string().min(1), author: z.string().optional(), isbn: z.string().optional() }),
    { canEdit: false, canDelete: false },
  ),
  'ops-transport': crud(
    [
      { name: 'registration_number', label: 'Registration', type: 'text', required: true },
      { name: 'make', label: 'Make', type: 'text' },
      { name: 'capacity', label: 'Capacity', type: 'number' },
    ],
    z.object({
      registration_number: z.string().min(1),
      make: z.string().optional(),
      capacity: z.coerce.number().optional(),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-transport-drivers': crud(
    [
      { name: 'name', label: 'Full name', type: 'text', required: true },
      { name: 'license_number', label: 'License number', type: 'text' },
      { name: 'phone', label: 'Phone', type: 'text' },
    ],
    z.object({ name: z.string().min(1), license_number: z.string().optional(), phone: z.string().optional() }),
    { canEdit: false, canDelete: false },
  ),
  'ops-transport-routes': crud(
    formSection('Route details', [
      { name: 'name', label: 'Route name', type: 'text', required: true },
      {
        name: 'vehicle_id',
        label: 'Vehicle',
        type: 'relation',
        placeholder: 'Select vehicle',
        relation: transportVehicleRelation(),
      },
      {
        name: 'driver_id',
        label: 'Driver',
        type: 'relation',
        placeholder: 'Select driver',
        relation: transportDriverRelation(),
      },
    ]),
    z.object({
      name: z.string().min(1),
      vehicle_id: z.string().optional().or(z.literal('')),
      driver_id: z.string().optional().or(z.literal('')),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-assets': crud(
    [
      { name: 'name', label: 'Asset name', type: 'text', required: true },
      { name: 'category', label: 'Category', type: 'text', required: true, placeholder: 'Furniture, IT, Lab…' },
      { name: 'purchase_date', label: 'Purchase date', type: 'date' },
    ],
    z.object({
      name: z.string().min(1),
      category: z.string().min(1, 'Category is required'),
      purchase_date: z.string().optional().or(z.literal('')),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-hostels': crud(
    [
      { name: 'name', label: 'Hostel name', type: 'text' },
      { name: 'capacity', label: 'Capacity', type: 'number' },
      { name: 'gender', label: 'Gender', type: 'select', options: [{ label: 'Male', value: 'male' }, { label: 'Female', value: 'female' }, { label: 'Mixed', value: 'mixed' }] },
    ],
    z.object({ name: z.string().min(1), capacity: z.coerce.number().optional(), gender: z.string().optional() }),
    { canEdit: false, canDelete: false },
  ),
  'ops-visitors': crud(
    [
      { name: 'name', label: 'Full name', type: 'text', required: true },
      { name: 'purpose', label: 'Purpose', type: 'text', required: true },
      { name: 'phone', label: 'Phone', type: 'text' },
    ],
    z.object({ name: z.string().min(1), purpose: z.string().min(1), phone: z.string().optional() }),
    { canEdit: false, canDelete: false },
  ),
  'ops-health': crud(
    formSection('Clinic visit', [
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        required: true,
        placeholder: 'Select student',
        colSpan: 2,
        relation: studentRelation(),
      },
      { name: 'visit_date', label: 'Visit date', type: 'date', required: true },
      { name: 'complaint', label: 'Complaint / reason', type: 'textarea', required: true, colSpan: 2 },
      { name: 'diagnosis', label: 'Diagnosis / notes', type: 'textarea', colSpan: 2 },
    ]),
    z.object({
      student_id: z.string().min(1, 'Select a student'),
      visit_date: z.string().min(1),
      complaint: z.string().min(1, 'Complaint is required'),
      diagnosis: z.string().optional(),
    }),
    { canEdit: false, canDelete: false },
  ),
  'ops-events': crud(
    [
      { name: 'title', label: 'Event title', type: 'text', required: true },
      { name: 'starts_at', label: 'Starts at', type: 'date', required: true },
      { name: 'location', label: 'Location', type: 'text' },
      { name: 'description', label: 'Description', type: 'textarea' },
    ],
    z.object({
      title: z.string().min(1),
      starts_at: z.string().min(1),
      location: z.string().optional(),
      description: z.string().optional(),
    }),
    { canEdit: false, canDelete: false },
  ),
  'comms-announcements': crud(
    [
      { name: 'title', label: 'Title', type: 'text' },
      { name: 'message', label: 'Message', type: 'textarea' },
      {
        name: 'type',
        label: 'Type',
        type: 'select',
        options: [
          { label: 'Information', value: 'info' },
          { label: 'Important', value: 'important' },
          { label: 'Warning', value: 'warning' },
          { label: 'Success', value: 'success' },
        ],
      },
      {
        name: 'target_audience',
        label: 'Audience',
        type: 'select',
        options: [
          { label: 'Everyone', value: 'all' },
          { label: 'Parents', value: 'parents' },
        ],
      },
      { name: 'date', label: 'Publish date', type: 'date' },
      { name: 'is_active', label: 'Visible', type: 'checkbox' },
    ],
    z.object({
      title: z.string().min(1),
      message: z.string().min(1),
      type: z.string().min(1),
      target_audience: z.enum(['all', 'parents']),
      date: z.string().min(1),
    }),
  ),
  'hr-leave': crud(
    leaveFormFields,
    leaveFormSchema,
    { canEdit: false, canDelete: false },
  ),
  'hr-discipline': crud(
    formSection('Incident record', [
      {
        name: 'student_id',
        label: 'Student',
        type: 'relation',
        required: true,
        placeholder: 'Select student',
        colSpan: 2,
        relation: studentRelation(),
      },
      { name: 'incident_date', label: 'Incident date', type: 'date', required: true },
      {
        name: 'category',
        label: 'Category',
        type: 'text',
        required: true,
        placeholder: 'Misconduct, bullying, lateness…',
      },
      {
        name: 'severity',
        label: 'Severity',
        type: 'select',
        required: true,
        options: [
          { label: 'Minor', value: 'minor' },
          { label: 'Moderate', value: 'moderate' },
          { label: 'Major', value: 'major' },
        ],
      },
      { name: 'description', label: 'Description', type: 'textarea', required: true, colSpan: 2 },
    ]),
    z.object({
      student_id: z.string().min(1, 'Select a student'),
      incident_date: z.string().min(1),
      category: z.string().min(1, 'Category is required'),
      severity: z.enum(['minor', 'moderate', 'major']),
      description: z.string().min(1, 'Description is required'),
    }),
    { canEdit: false, canDelete: false },
  ),
  compliance: crud(
    formSection('Policy', [
      { name: 'title', label: 'Policy title', type: 'text', required: true },
      { name: 'category', label: 'Category', type: 'text' },
      { name: 'content', label: 'Content', type: 'textarea', required: true, colSpan: 2 },
    ]),
    z.object({ title: z.string().min(1), category: z.string().optional(), content: z.string().min(1) }),
    { canEdit: false, canDelete: false },
  ),
  'compliance-incidents': crud(
    formSection('Incident report', [
      { name: 'category', label: 'Category', type: 'text', required: true },
      {
        name: 'severity',
        label: 'Severity',
        type: 'select',
        options: [
          { label: 'Low', value: 'low' },
          { label: 'Medium', value: 'medium' },
          { label: 'High', value: 'high' },
          { label: 'Critical', value: 'critical' },
        ],
      },
      { name: 'description', label: 'Description', type: 'textarea', required: true, colSpan: 2 },
    ]),
    z.object({
      category: z.string().min(1),
      severity: z.enum(['low', 'medium', 'high', 'critical']).optional().or(z.literal('')),
      description: z.string().min(1),
    }),
    { canEdit: false, canDelete: false },
  ),
  'compliance-consent': crud(
    [
      { name: 'title', label: 'Form title', type: 'text', required: true },
      { name: 'content', label: 'Form content', type: 'textarea', required: true, colSpan: 2 },
    ],
    z.object({ title: z.string().min(1), content: z.string().min(1, 'Form content is required') }),
    { canEdit: false, canDelete: false },
  ),
  'settings-custom-fields': crud(
    [
      { name: 'entity_type', label: 'Entity type', type: 'text', required: true, placeholder: 'student' },
      { name: 'field_name', label: 'Field name', type: 'text', required: true },
      { name: 'field_type', label: 'Field type', type: 'text', required: true, placeholder: 'text' },
    ],
    z.object({
      entity_type: z.string().min(1),
      field_name: z.string().min(1),
      field_type: z.string().min(1),
    }),
    { canEdit: false },
  ),
}
