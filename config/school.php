<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Default System Terminology
  |--------------------------------------------------------------------------
  | Schools can override these via terminology_mappings table.
  */
  'defaults' => [
    'class' => 'Class',
    'classes' => 'Classes',
    'grade' => 'Grade',
    'grades' => 'Grades',
    'grade_level' => 'Grade Level',
    'student' => 'Student',
    'students' => 'Students',
    'teacher' => 'Teacher',
    'teachers' => 'Teachers',
    'staff' => 'Staff',
    'term' => 'Term',
    'terms' => 'Terms',
    'subject' => 'Subject',
    'subjects' => 'Subjects',
    'attendance' => 'Attendance',
    'invoice' => 'Invoice',
    'invoices' => 'Invoices',
    'payment' => 'Payment',
    'payments' => 'Payments',
    'guardian' => 'Guardian',
    'guardians' => 'Guardians',
    'cohort' => 'Cohort',
    'academic_year' => 'Academic Year',
  ],

  /*
  |--------------------------------------------------------------------------
  | Typed School Setting Definitions
  |--------------------------------------------------------------------------
  */
  'definitions' => [
    'regional' => [
      'currency' => ['type' => 'string', 'default' => 'USD', 'public' => true],
      'date_format' => ['type' => 'string', 'default' => 'Y-m-d', 'public' => true],
      'time_format' => ['type' => 'string', 'default' => 'H:i', 'public' => true],
      'timezone' => ['type' => 'string', 'default' => 'UTC', 'public' => true],
      'locale' => ['type' => 'string', 'default' => 'en', 'public' => true],
    ],
    'academic' => [
      'academic_year' => ['type' => 'string', 'default' => null, 'public' => true],
      'current_term' => ['type' => 'string', 'default' => 'Term 1', 'public' => true],
      'term_structure' => ['type' => 'json', 'default' => ['Term 1', 'Term 2', 'Term 3'], 'public' => true],
      'grading_scale_type' => ['type' => 'string', 'default' => 'letter', 'public' => true],
      'pass_mark' => ['type' => 'integer', 'default' => 50, 'public' => true],
    ],
    'branding' => [
      'school_name' => ['type' => 'string', 'default' => null, 'public' => true],
      'motto' => ['type' => 'string', 'default' => '', 'public' => true],
      'primary_color' => ['type' => 'string', 'default' => '#FF7A00', 'public' => true],
    ],
  ],
];
