import type { RelationFieldConfig } from '@/components/forms/useFormBuilder'
import { endpoints, moduleEndpoints } from '@/services'
import { schoolSetupHref } from '@/modules/settings/school-setup-links'

export function studentRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.students,
    createRoute: '/people?tab=students&create=1',
    moduleLabel: 'student',
    ...overrides,
  }
}

export function guardianRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.guardians,
    createRoute: '/people?tab=guardians&create=1',
    moduleLabel: 'guardian',
    ...overrides,
  }
}

export function classRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.classes,
    createRoute: schoolSetupHref('classes', { create: true }),
    moduleLabel: 'class',
    ...overrides,
  }
}

export function inventoryItemRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.inventoryItems,
    createRoute: '/operations?tab=inventory&create=1&section=ops-inventory',
    moduleLabel: 'inventory item',
    ...overrides,
  }
}

export function inventorySaleRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.inventorySales,
    createRoute: '/operations?tab=inventory&create=1&section=ops-inventory-sales',
    moduleLabel: 'inventory sale',
    ...overrides,
  }
}

export function transportVehicleRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.transportVehicles,
    createRoute: '/operations?tab=transport&create=1&section=ops-transport',
    moduleLabel: 'vehicle',
    ...overrides,
  }
}

export function transportDriverRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.transportDrivers,
    createRoute: '/operations?tab=transport&create=1&section=ops-transport-drivers',
    moduleLabel: 'driver',
    ...overrides,
  }
}

export function transportRouteRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.transportRoutes,
    createRoute: '/operations?tab=transport&create=1&section=ops-transport-routes',
    moduleLabel: 'transport route',
    ...overrides,
  }
}

export function procurementVendorRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.procurementVendors,
    createRoute: '/finance?tab=procurement&create=1&section=ops-procurement-vendors',
    moduleLabel: 'vendor',
    ...overrides,
  }
}

export function subjectRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.subjects,
    createRoute: schoolSetupHref('subjects', { create: true }),
    moduleLabel: 'subject',
    fallbackRowKey: 'subject',
    ...overrides,
  }
}

export function departmentRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.departments,
    createRoute: schoolSetupHref('departments', { create: true }),
    moduleLabel: 'department',
    fallbackRowKey: 'department',
    ...overrides,
  }
}

export function gradeLevelRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.gradeLevels,
    createRoute: schoolSetupHref('grade-levels', { create: true }),
    moduleLabel: 'grade level',
    ...overrides,
  }
}

export function streamRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.streams,
    createRoute: schoolSetupHref('streams', { create: true }),
    moduleLabel: 'stream',
    ...overrides,
  }
}

export function feeCategoryRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.feeCategories,
    createRoute: '/finance?tab=fee-categories&create=1',
    moduleLabel: 'fee category',
    params: { all: true },
    ...overrides,
  }
}

export function studentCategoryRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.studentCategories,
    createRoute: '/people?tab=student-categories&create=1',
    moduleLabel: 'student category',
    ...overrides,
  }
}

export function leaveTypeRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.leaveTypes,
    createRoute: '/hr?tab=leave-types&create=1',
    moduleLabel: 'leave type',
    ...overrides,
  }
}

export function designationRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.designations,
    createRoute: '/hr?tab=designations&create=1',
    moduleLabel: 'designation',
    ...overrides,
  }
}

export function feeGroupRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.feeGroups,
    createRoute: '/finance?tab=fee-groups&create=1',
    moduleLabel: 'fee group',
    ...overrides,
  }
}

export function feeStructureRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.feeStructures,
    createRoute: '/finance?tab=fee-structures&create=1',
    moduleLabel: 'fee structure',
    ...overrides,
  }
}

export function incomeHeadRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.incomeHeads,
    createRoute: '/finance?tab=income-heads&create=1',
    moduleLabel: 'income head',
    params: { all: true, is_active: true },
    ...overrides,
  }
}

export function expenseHeadRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.expenseHeads,
    createRoute: '/finance?tab=expense-heads&create=1',
    moduleLabel: 'expense head',
    params: { all: true, is_active: true },
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
    createRoute: '/finance?tab=invoices&create=1',
    moduleLabel: 'invoice',
    params: { status: 'pending,partial,overdue' },
    ...overrides,
  }
}

export function staffUserRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.users,
    createRoute: '/admin?tab=users&create=1',
    moduleLabel: 'staff host',
    ...overrides,
  }
}

export function roomRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.rooms,
    createRoute: schoolSetupHref('rooms', { create: true }),
    moduleLabel: 'room',
    ...overrides,
  }
}

export function termRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.terms,
    createRoute: schoolSetupHref('academic-setup', { create: true }),
    moduleLabel: 'term',
    ...overrides,
  }
}

export function examRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.exams,
    createRoute: '/academics/exams',
    moduleLabel: 'exam',
    ...overrides,
  }
}

export function libraryBookRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.libraryBooks,
    createRoute: '/operations?tab=library-books&create=1',
    moduleLabel: 'library book',
    ...overrides,
  }
}

export function libraryMemberRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.libraryMembers,
    createRoute: '/operations?tab=library-members&create=1',
    moduleLabel: 'library member',
    ...overrides,
  }
}

export function employeeRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.employees,
    createRoute: '/hr?tab=employees&create=1',
    moduleLabel: 'employee',
    ...overrides,
  }
}

export function jobPostingRelation(overrides?: Partial<RelationFieldConfig>): RelationFieldConfig {
  return {
    endpoint: moduleEndpoints.recruitmentJobs,
    createRoute: '/hr?tab=recruitment-jobs&create=1',
    moduleLabel: 'job posting',
    params: { status: 'open', all: true },
    ...overrides,
  }
}
