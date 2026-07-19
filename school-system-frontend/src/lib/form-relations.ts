import type { RelationFieldConfig } from '@/components/forms/useFormBuilder'
import { endpoints, moduleEndpoints } from '@/services'

export function studentRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.students,
    createRoute: '/students?create=1',
    moduleLabel: 'student',
    ...overrides,
  }
}

export function guardianRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.guardians,
    createRoute: '/guardians?create=1',
    moduleLabel: 'guardian',
    ...overrides,
  }
}

export function classRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.classes,
    createRoute: '/academics/setup?create=1',
    moduleLabel: 'class',
    ...overrides,
  }
}

export function inventoryItemRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.inventoryItems,
    createRoute: '/operations/inventory?create=1',
    moduleLabel: 'inventory item',
    ...overrides,
  }
}

export function transportVehicleRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.transportVehicles,
    createRoute: '/operations/transport?create=1',
    moduleLabel: 'vehicle',
    ...overrides,
  }
}

export function transportDriverRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.transportDrivers,
    createRoute: '/operations/transport/drivers?create=1',
    moduleLabel: 'driver',
    ...overrides,
  }
}

export function subjectRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.subjects,
    createRoute: '/academics/subjects?create=1',
    moduleLabel: 'subject',
    fallbackRowKey: 'subject',
    ...overrides,
  }
}

export function departmentRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.departments,
    createRoute: '/academics/departments?create=1',
    moduleLabel: 'department',
    fallbackRowKey: 'department',
    ...overrides,
  }
}

export function feeCategoryRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.feeCategories,
    createRoute: '/finance/fee-categories?create=1',
    moduleLabel: 'fee category',
    params: { all: true },
    ...overrides,
  }
}

export function payrollTeacherRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: endpoints.payroll.teachers,
    moduleLabel: 'staff member',
    ...overrides,
  }
}

export function invoiceRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.invoices,
    moduleLabel: 'invoice',
    params: { status: 'pending,partial,overdue' },
    ...overrides,
  }
}

export function staffUserRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.users,
    moduleLabel: 'staff host',
    ...overrides,
  }
}
