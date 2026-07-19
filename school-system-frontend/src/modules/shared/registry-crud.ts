import { z } from 'zod'
import type { FormFieldSchema } from '@/components/forms/useFormBuilder'
import { formSection } from '@/lib/form-standards'
import { PAYMENT_METHOD_OPTIONS } from '@/lib/finance-constants'
import {
  inventoryItemRelation,
  invoiceRelation,
  payrollTeacherRelation,
  studentRelation,
  staffUserRelation,
  transportDriverRelation,
  transportVehicleRelation,
  classRelation,
  feeCategoryRelation,
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
        name: 'stream_id',
        label: 'Stream',
        type: 'relation',
        section: 'Class details',
        placeholder: 'Optional stream',
        colSpan: 1,
        relation: { endpoint: moduleEndpoints.streams },
      },
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
      stream_id: z.string().optional().or(z.literal('')),
      teacher_id: z.string().optional().or(z.literal('')),
    }),
  ),
  'academics-streams': crud(
    [
      { name: 'name', label: 'Stream name', type: 'text', required: true, section: 'Stream', colSpan: 1 },
      { name: 'code', label: 'Code', type: 'text', section: 'Stream', colSpan: 1 },
      { name: 'description', label: 'Description', type: 'textarea', section: 'Stream', colSpan: 2 },
    ],
    z.object({
      name: z.string().min(1),
      code: z.string().optional(),
      description: z.string().optional(),
    }),
  ),
  'academics-houses': crud(
    [
      { name: 'name', label: 'House name', type: 'text', required: true, section: 'House', colSpan: 1 },
      { name: 'code', label: 'Code', type: 'text', section: 'House', colSpan: 1 },
      { name: 'color', label: 'Colour', type: 'text', section: 'House', colSpan: 1 },
      {
        name: 'teacher_id',
        label: 'House master / mistress',
        type: 'relation',
        section: 'House',
        colSpan: 1,
        relation: { endpoint: moduleEndpoints.teachers },
      },
    ],
    z.object({
      name: z.string().min(1),
      code: z.string().optional(),
      color: z.string().optional(),
      teacher_id: z.string().optional().or(z.literal('')),
    }),
  ),
  'academics-subject-packages': crud(
    [
      {
        name: 'grade_level_id',
        label: 'Grade level',
        type: 'relation',
        required: true,
        section: 'Package',
        relation: { endpoint: moduleEndpoints.gradeLevels },
      },
      {
        name: 'subject_id',
        label: 'Subject',
        type: 'relation',
        required: true,
        section: 'Package',
        relation: { endpoint: moduleEndpoints.subjects },
      },
      {
        name: 'stream_id',
        label: 'Stream (optional)',
        type: 'relation',
        section: 'Package',
        relation: { endpoint: moduleEndpoints.streams },
      },
      {
        name: 'is_core',
        label: 'Core subject',
        type: 'select',
        section: 'Package',
        options: [
          { label: 'Core', value: 'true' },
          { label: 'Elective', value: 'false' },
        ],
      },
    ],
    z.object({
      grade_level_id: z.string().min(1),
      subject_id: z.string().min(1),
      stream_id: z.string().optional().or(z.literal('')),
      is_core: z.string().optional(),
    }),
    { canEdit: false, canDelete: false },
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
    [
      ...formSection('Term details', [
        { name: 'name', label: 'Term name', type: 'text', required: true, placeholder: 'Term 1' },
        {
          name: 'academic_year',
          label: 'Academic year',
          type: 'text',
          required: true,
          placeholder: '2026-2027',
          description: 'Use the school year range, e.g. 2026-2027.',
        },
        { name: 'start_date', label: 'Start date', type: 'date', required: true },
        { name: 'end_date', label: 'End date', type: 'date', required: true },
        {
          name: 'order',
          label: 'Order',
          type: 'number',
          placeholder: '1',
          description: 'Display order within the academic year (1, 2, 3…).',
        },
        {
          name: 'description',
          label: 'Description',
          type: 'textarea',
          colSpan: 2,
          placeholder: 'Optional notes for this term',
        },
      ]),
      {
        name: 'is_current',
        label: 'Current term',
        type: 'checkbox',
        section: 'Status',
        description: 'Only one term should be current per school. Used by gradebook and exams.',
      },
      {
        name: 'is_active',
        label: 'Active',
        type: 'checkbox',
        section: 'Status',
        description: 'Inactive terms stay in history but are hidden from new work.',
      },
    ],
    z
      .object({
        name: z.string().min(1, 'Term name is required'),
        academic_year: z.string().min(4, 'Academic year is required'),
        start_date: z.string().min(1, 'Start date is required'),
        end_date: z.string().min(1, 'End date is required'),
        order: z.coerce.number().int().min(1).optional(),
        description: z.string().optional(),
        is_current: z.boolean().optional(),
        is_active: z.boolean().optional(),
      })
      .refine((v) => !v.start_date || !v.end_date || v.end_date > v.start_date, {
        message: 'End date must be after start date',
        path: ['end_date'],
      }),
    { staged: false },
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
        name: 'class_id',
        label: 'Class',
        type: 'relation',
        required: true,
        placeholder: 'Select class',
        description: 'Class this fee structure applies to.',
        relation: classRelation(),
      },
      {
        name: 'fee_category_id',
        label: 'Fee category',
        type: 'relation',
        required: true,
        placeholder: 'Select category',
        description: 'Choose from Fee Categories. Create a category first if needed.',
        relation: feeCategoryRelation(),
      },
      { name: 'amount', label: 'Amount', type: 'number', required: true },
      {
        name: 'currency',
        label: 'Currency',
        type: 'select',
        required: true,
        options: [
          { label: 'USD', value: 'USD' },
          { label: 'ZWG', value: 'ZWG' },
        ],
      },
    ],
    z.object({
      class_id: z.string().min(1, 'Select a class'),
      fee_category_id: z.string().min(1, 'Select a fee category'),
      amount: z.coerce.number().positive('Amount must be greater than zero'),
      currency: z.enum(['USD', 'ZWG']),
    }),
  ),
  'finance-fee-categories': crud(
    [
      { name: 'name', label: 'Category name', type: 'text', required: true },
      { name: 'description', label: 'Description', type: 'textarea' },
      {
        name: 'is_active',
        label: 'Active',
        type: 'checkbox',
        description: 'Inactive categories stay in history but are hidden from new fee structures.',
      },
      {
        name: 'order',
        label: 'Display order',
        type: 'number',
        placeholder: '0',
        description: 'Lower numbers appear first in pickers.',
      },
    ],
    z.object({
      name: z.string().min(1, 'Name is required'),
      description: z.string().optional(),
      is_active: z.boolean().optional(),
      order: z.coerce.number().min(0).optional(),
    }),
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
      ...formSection('Item details', [
        { name: 'name', label: 'Item name', type: 'text', required: true, placeholder: 'School blazer' },
        { name: 'sku', label: 'SKU', type: 'text', placeholder: 'BLZ-M' },
        {
          name: 'type',
          label: 'Type',
          type: 'select',
          required: true,
          options: [
            { label: 'Uniform', value: 'uniform' },
            { label: 'Stationery', value: 'stationery' },
            { label: 'Book', value: 'book' },
            { label: 'Equipment', value: 'equipment' },
            { label: 'Other', value: 'other' },
          ],
        },
        { name: 'size', label: 'Size / variant', type: 'text', placeholder: 'M, A4, …' },
        {
          name: 'description',
          label: 'Description',
          type: 'textarea',
          colSpan: 2,
          placeholder: 'Optional notes for this item',
        },
      ]),
      ...formSection('Stock & pricing', [
        { name: 'stock_quantity', label: 'Stock on hand', type: 'number', required: true, placeholder: '0' },
        { name: 'reorder_level', label: 'Reorder level', type: 'number', placeholder: '5' },
        { name: 'unit_price', label: 'Unit price', type: 'number', required: true, placeholder: '0.00' },
        {
          name: 'currency',
          label: 'Currency',
          type: 'select',
          required: true,
          options: [
            { label: 'USD', value: 'USD' },
            { label: 'ZWG', value: 'ZWG' },
          ],
        },
        {
          name: 'billing_mode',
          label: 'Billing mode',
          type: 'select',
          required: true,
          options: [
            { label: 'Direct sale', value: 'direct_sale' },
            { label: 'Mandatory fee', value: 'mandatory_fee' },
            { label: 'Both', value: 'both' },
          ],
        },
      ]),
      {
        name: 'is_active',
        label: 'Active',
        type: 'checkbox',
        section: 'Status',
        description: 'Inactive items stay in history but are hidden from new sales.',
      },
    ],
    z.object({
      name: z.string().min(1, 'Item name is required'),
      sku: z.string().optional(),
      type: z.enum(['uniform', 'stationery', 'book', 'equipment', 'other']),
      size: z.string().optional(),
      description: z.string().optional(),
      stock_quantity: z.coerce.number().min(0),
      reorder_level: z.coerce.number().min(0).optional(),
      unit_price: z.coerce.number().min(0),
      currency: z.enum(['USD', 'ZWG']),
      billing_mode: z.enum(['direct_sale', 'mandatory_fee', 'both']),
      is_active: z.boolean().optional(),
    }),
    { canDelete: false, staged: false },
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
    formSection('Vehicle details', [
      {
        name: 'registration_number',
        label: 'Registration number',
        type: 'text',
        required: true,
        placeholder: 'AEB 1234',
      },
      { name: 'make', label: 'Make', type: 'text', placeholder: 'Toyota' },
      { name: 'model', label: 'Model', type: 'text', placeholder: 'Hiace' },
      { name: 'capacity', label: 'Seat capacity', type: 'number', placeholder: '16' },
      {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
          { label: 'Maintenance', value: 'maintenance' },
        ],
      },
    ]),
    z.object({
      registration_number: z.string().min(1, 'Registration is required'),
      make: z.string().optional(),
      model: z.string().optional(),
      capacity: z.coerce.number().int().min(1).optional(),
      status: z.enum(['active', 'inactive', 'maintenance']),
    }),
    { canEdit: true, canDelete: false, staged: false },
  ),
  'ops-transport-drivers': crud(
    formSection('Driver details', [
      { name: 'name', label: 'Full name', type: 'text', required: true },
      { name: 'license_number', label: 'License number', type: 'text' },
      { name: 'phone', label: 'Phone', type: 'phone' },
      {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ]),
    z.object({
      name: z.string().min(1, 'Name is required'),
      license_number: z.string().optional(),
      phone: z.string().optional(),
      status: z.enum(['active', 'inactive']),
    }),
    { canEdit: true, canDelete: false, staged: false },
  ),
  'ops-transport-routes': crud(
    formSection('Route details', [
      { name: 'name', label: 'Route name', type: 'text', required: true, placeholder: 'Mufakose Central' },
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
      {
        name: 'route_description',
        label: 'Description',
        type: 'textarea',
        colSpan: 2,
        placeholder: 'Pickup points and notes',
      },
      {
        name: 'status',
        label: 'Status',
        type: 'select',
        required: true,
        options: [
          { label: 'Active', value: 'active' },
          { label: 'Inactive', value: 'inactive' },
        ],
      },
    ]),
    z.object({
      name: z.string().min(1, 'Route name is required'),
      vehicle_id: z.string().optional().or(z.literal('')),
      driver_id: z.string().optional().or(z.literal('')),
      route_description: z.string().optional(),
      status: z.enum(['active', 'inactive']),
    }),
    { canEdit: true, canDelete: false, staged: false },
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
    formSection('Visitor check-in', [
      { name: 'name', label: 'Full name', type: 'text', required: true, placeholder: 'Visitor name' },
      { name: 'phone', label: 'Phone', type: 'phone' },
      { name: 'id_number', label: 'ID / passport number', type: 'text', placeholder: 'Optional' },
      { name: 'purpose', label: 'Purpose of visit', type: 'text', required: true, placeholder: 'Meeting, pickup, delivery…', colSpan: 2 },
      {
        name: 'host_user_id',
        label: 'Host staff',
        type: 'relation',
        placeholder: 'Optional — who they are visiting',
        relation: staffUserRelation(),
      },
      {
        name: 'student_id',
        label: 'Related student',
        type: 'relation',
        placeholder: 'Optional — if visiting a student',
        relation: studentRelation(),
      },
    ]),
    z.object({
      name: z.string().min(1, 'Name is required'),
      phone: z.string().optional(),
      id_number: z.string().optional(),
      purpose: z.string().min(1, 'Purpose is required'),
      host_user_id: z.string().optional().or(z.literal('')),
      student_id: z.string().optional().or(z.literal('')),
    }),
    { canEdit: false, canDelete: false, staged: false },
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
