CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "payments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "parent_id" integer,
  "invoice_id" integer,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "method" varchar not null,
  "reference" varchar,
  "status" varchar not null default 'completed',
  "date" date not null,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("parent_id") references "users"("id") on delete set null,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "payments_school_id_index" on "payments"("school_id");
CREATE INDEX "payments_student_id_index" on "payments"("student_id");
CREATE INDEX "payments_parent_id_index" on "payments"("parent_id");
CREATE INDEX "payments_invoice_id_index" on "payments"("invoice_id");
CREATE TABLE IF NOT EXISTS "announcements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "title" varchar not null,
  "message" text not null,
  "type" varchar not null default 'info',
  "target_audience" varchar not null default 'all',
  "date" date not null,
  "is_active" tinyint(1) not null default '1',
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE INDEX "announcements_school_id_index" on "announcements"("school_id");
CREATE INDEX "announcements_school_id_is_active_date_index" on "announcements"(
  "school_id",
  "is_active",
  "date"
);
CREATE TABLE IF NOT EXISTS "assignments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "title" varchar not null,
  "description" text,
  "subject" varchar not null,
  "class_id" integer not null,
  "teacher_id" integer not null,
  "due_date" date not null,
  "total_marks" numeric not null default '100',
  "status" varchar not null default 'active',
  "submissions_count" integer not null default '0',
  "instructions" text,
  "attachment_url" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "submission_type" varchar not null default 'file',
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade
);
CREATE INDEX "assignments_school_id_index" on "assignments"("school_id");
CREATE INDEX "assignments_class_id_due_date_index" on "assignments"(
  "class_id",
  "due_date"
);
CREATE TABLE IF NOT EXISTS "parent_student"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "parent_id" integer not null,
  "student_id" integer not null,
  "relationship" varchar not null default 'parent',
  "is_primary" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("parent_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "parent_student_parent_id_student_id_unique" on "parent_student"(
  "parent_id",
  "student_id"
);
CREATE INDEX "parent_student_school_id_index" on "parent_student"("school_id");
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "category" varchar not null default 'general',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "settings_school_id_key_unique" on "settings"(
  "school_id",
  "key"
);
CREATE INDEX "settings_school_id_index" on "settings"("school_id");
CREATE TABLE IF NOT EXISTS "departments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "head_teacher_id" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("head_teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "departments_school_id_name_unique" on "departments"(
  "school_id",
  "name"
);
CREATE INDEX "departments_school_id_index" on "departments"("school_id");
CREATE TABLE IF NOT EXISTS "fee_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_categories_school_id_name_unique" on "fee_categories"(
  "school_id",
  "name"
);
CREATE INDEX "fee_categories_school_id_index" on "fee_categories"("school_id");
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  "permission_ids" text
);
CREATE UNIQUE INDEX "roles_slug_unique" on "roles"("slug");
CREATE TABLE IF NOT EXISTS "subjects"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "class_id" integer,
  "teacher_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "subjects_school_id_name_unique" on "subjects"(
  "school_id",
  "name"
);
CREATE INDEX "subjects_school_id_index" on "subjects"("school_id");
CREATE TABLE IF NOT EXISTS "payroll"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "month" integer not null,
  "year" integer not null,
  "base_salary" numeric not null,
  "gross_salary" numeric not null,
  "allowances" text,
  "deductions" text,
  "allowances_total" numeric not null default '0',
  "deductions_total" numeric not null default '0',
  "net_salary" numeric not null,
  "currency" varchar not null default 'USD',
  "status" varchar not null default 'pending',
  "paid_at" date,
  "payment_method" varchar,
  "payment_reference" varchar,
  "processed_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "amount_paid" numeric not null default '0',
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("processed_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "payroll_school_id_teacher_id_month_year_unique" on "payroll"(
  "school_id",
  "teacher_id",
  "month",
  "year"
);
CREATE INDEX "payroll_school_id_status_index" on "payroll"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "report_templates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "name" varchar not null,
  "description" text,
  "category" varchar not null,
  "frequency" varchar,
  "recipients" text,
  "parameters" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "report_templates_school_id_index" on "report_templates"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "grade_levels"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "order" integer not null default '0',
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "grade_levels_school_id_name_unique" on "grade_levels"(
  "school_id",
  "name"
);
CREATE UNIQUE INDEX "grade_levels_school_id_code_unique" on "grade_levels"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "rooms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "type" varchar,
  "capacity" integer,
  "description" text,
  "location" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "rooms_school_id_name_unique" on "rooms"(
  "school_id",
  "name"
);
CREATE UNIQUE INDEX "rooms_school_id_code_unique" on "rooms"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "grading_scales"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "grade" varchar not null,
  "min_score" numeric not null,
  "max_score" numeric not null,
  "description" varchar,
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "grade_point" numeric default '0',
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "grading_scales_school_id_grade_unique" on "grading_scales"(
  "school_id",
  "grade"
);
CREATE INDEX "grading_scales_school_id_min_score_max_score_index" on "grading_scales"(
  "school_id",
  "min_score",
  "max_score"
);
CREATE TABLE IF NOT EXISTS "teacher_assignments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "grade_level_id" integer,
  "class_id" integer,
  "subject_id" integer,
  "role" varchar,
  "assigned_at" date not null default '2026-07-28 16:04:50',
  "assigned_until" date,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("grade_level_id") references "grade_levels"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "teacher_assignments_school_id_teacher_id_index" on "teacher_assignments"(
  "school_id",
  "teacher_id"
);
CREATE INDEX "teacher_assignments_school_id_class_id_index" on "teacher_assignments"(
  "school_id",
  "class_id"
);
CREATE INDEX "teacher_assignments_school_id_grade_level_id_index" on "teacher_assignments"(
  "school_id",
  "grade_level_id"
);
CREATE TABLE IF NOT EXISTS "guardians"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "email" varchar,
  "phone" varchar not null,
  "relationship" varchar,
  "address" text,
  "national_id" varchar,
  "occupation" varchar,
  "is_primary" tinyint(1) not null default '0',
  "can_receive_notifications" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "guardians_school_id_email_index" on "guardians"(
  "school_id",
  "email"
);
CREATE INDEX "guardians_school_id_phone_index" on "guardians"(
  "school_id",
  "phone"
);
CREATE TABLE IF NOT EXISTS "guardian_student"(
  "id" integer primary key autoincrement not null,
  "guardian_id" integer not null,
  "student_id" integer not null,
  "relationship" varchar,
  "is_primary" tinyint(1) not null default '0',
  "can_pickup" tinyint(1) not null default '1',
  "emergency_contact" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("guardian_id") references "guardians"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "guardian_student_guardian_id_student_id_unique" on "guardian_student"(
  "guardian_id",
  "student_id"
);
CREATE INDEX "guardian_student_student_id_index" on "guardian_student"(
  "student_id"
);
CREATE TABLE IF NOT EXISTS "terms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "academic_year" varchar not null,
  "start_date" date not null,
  "end_date" date not null,
  "is_current" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "terms_school_id_academic_year_name_unique" on "terms"(
  "school_id",
  "academic_year",
  "name"
);
CREATE INDEX "terms_school_id_academic_year_is_current_index" on "terms"(
  "school_id",
  "academic_year",
  "is_current"
);
CREATE INDEX "terms_school_id_is_active_index" on "terms"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "tests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer not null,
  "teacher_id" integer not null,
  "term_id" integer,
  "name" varchar not null,
  "description" text,
  "test_date" date not null,
  "start_time" time,
  "end_time" time,
  "total_marks" numeric not null default '100',
  "passing_marks" numeric,
  "academic_year" varchar not null,
  "is_published" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE INDEX "tests_school_id_class_id_subject_id_index" on "tests"(
  "school_id",
  "class_id",
  "subject_id"
);
CREATE INDEX "tests_school_id_teacher_id_index" on "tests"(
  "school_id",
  "teacher_id"
);
CREATE INDEX "tests_school_id_test_date_index" on "tests"(
  "school_id",
  "test_date"
);
CREATE TABLE IF NOT EXISTS "test_results"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "test_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "marks_obtained" numeric not null,
  "total_marks" numeric not null,
  "percentage" numeric,
  "grade" varchar,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("test_id") references "tests"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade
);
CREATE UNIQUE INDEX "test_results_test_id_student_id_unique" on "test_results"(
  "test_id",
  "student_id"
);
CREATE INDEX "test_results_school_id_test_id_index" on "test_results"(
  "school_id",
  "test_id"
);
CREATE INDEX "test_results_school_id_student_id_index" on "test_results"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "role" varchar not null default('admin'),
  "school_id" integer,
  "phone" varchar,
  "address" text,
  "date_of_birth" date,
  "gender" varchar,
  "first_name" varchar,
  "last_name" varchar,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'active',
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "avatar_url" varchar,
  "platform_terms_version" varchar,
  "platform_terms_accepted_at" datetime,
  "permission_ids" text,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_role_index" on "users"("role");
CREATE INDEX "users_school_id_index" on "users"("school_id");
CREATE INDEX "users_status_index" on "users"("status");
CREATE TABLE IF NOT EXISTS "school_settings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "group" varchar not null default 'general',
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_settings_school_id_group_key_unique" on "school_settings"(
  "school_id",
  "group",
  "key"
);
CREATE INDEX "school_settings_school_id_group_index" on "school_settings"(
  "school_id",
  "group"
);
CREATE TABLE IF NOT EXISTS "custom_fields"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "entity_type" varchar not null,
  "name" varchar not null,
  "slug" varchar not null,
  "field_type" varchar not null default 'text',
  "options" text,
  "is_required" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "custom_fields_school_id_entity_type_slug_unique" on "custom_fields"(
  "school_id",
  "entity_type",
  "slug"
);
CREATE INDEX "custom_fields_school_id_entity_type_is_active_index" on "custom_fields"(
  "school_id",
  "entity_type",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "custom_field_values"(
  "id" integer primary key autoincrement not null,
  "custom_field_id" integer not null,
  "entity_type" varchar not null,
  "entity_id" integer not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("custom_field_id") references "custom_fields"("id") on delete cascade
);
CREATE INDEX "custom_field_values_entity_type_entity_id_index" on "custom_field_values"(
  "entity_type",
  "entity_id"
);
CREATE UNIQUE INDEX "custom_field_values_unique" on "custom_field_values"(
  "custom_field_id",
  "entity_type",
  "entity_id"
);
CREATE TABLE IF NOT EXISTS "terminology_mappings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "system_key" varchar not null,
  "custom_label" varchar not null,
  "locale" varchar not null default 'en',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "terminology_mappings_school_id_system_key_locale_unique" on "terminology_mappings"(
  "school_id",
  "system_key",
  "locale"
);
CREATE INDEX "terminology_mappings_school_id_locale_index" on "terminology_mappings"(
  "school_id",
  "locale"
);
CREATE TABLE IF NOT EXISTS "passkeys"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "credential_id" varchar not null,
  "credential" text not null,
  "last_used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "passkeys_user_id_index" on "passkeys"("user_id");
CREATE UNIQUE INDEX "passkeys_credential_id_unique" on "passkeys"(
  "credential_id"
);
CREATE TABLE IF NOT EXISTS "agent_conversation_messages"(
  "id" varchar not null,
  "conversation_id" varchar not null,
  "user_id" integer,
  "agent" varchar not null,
  "role" varchar not null,
  "content" text not null,
  "attachments" text not null,
  "tool_calls" text not null,
  "tool_results" text not null,
  "usage" text not null,
  "meta" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "conversation_index" on "agent_conversation_messages"(
  "conversation_id",
  "user_id",
  "updated_at"
);
CREATE INDEX "agent_conversation_messages_user_id_index" on "agent_conversation_messages"(
  "user_id"
);
CREATE INDEX "agent_conversation_messages_conversation_id_index" on "agent_conversation_messages"(
  "conversation_id"
);
CREATE TABLE IF NOT EXISTS "grades"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "subject" varchar not null,
  "subject_id" integer,
  "assessment_type" varchar not null default('test'),
  "score" numeric not null,
  "total" numeric not null default('100'),
  "grade" varchar,
  "term" varchar,
  "year" integer,
  "class_id" integer,
  "teacher_id" integer,
  "grade_level_id" integer,
  "grading_scale_id" integer,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  "test_id" integer,
  "assignment_id" integer,
  foreign key("assignment_id") references assignments("id") on delete set null on update no action,
  foreign key("test_id") references tests("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("grading_scale_id") references grading_scales("id") on delete set null on update no action,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "grades_school_id_index" on "grades"("school_id");
CREATE INDEX "grades_student_id_index" on "grades"("student_id");
CREATE INDEX "grades_student_id_year_term_index" on "grades"(
  "student_id",
  "year",
  "term"
);
CREATE TABLE IF NOT EXISTS "timetable"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "class_id" integer not null,
  "teacher_id" integer,
  "subject_id" integer,
  "subject" varchar not null,
  "room_id" integer,
  "room" varchar,
  "day" varchar not null,
  "start_time" time not null,
  "end_time" time not null,
  "lesson_type" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("room_id") references rooms("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("class_id") references classes("id") on delete cascade on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "timetable_class_id_day_index" on "timetable"("class_id", "day");
CREATE INDEX "timetable_school_id_index" on "timetable"("school_id");
CREATE INDEX "timetable_teacher_id_index" on "timetable"("teacher_id");
CREATE TABLE IF NOT EXISTS "agent_conversations"(
  "id" varchar not null,
  "user_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "agent_conversations_user_id_updated_at_index" on "agent_conversations"(
  "user_id",
  "updated_at"
);
CREATE TABLE IF NOT EXISTS "student_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "uploaded_by" integer,
  "name" varchar not null,
  "type" varchar,
  "path" varchar not null,
  "mime_type" varchar,
  "size" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete set null
);
CREATE INDEX "student_documents_school_id_student_id_index" on "student_documents"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "enrollment_applications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "first_name" varchar not null,
  "surname" varchar not null,
  "date_of_birth" date not null,
  "gender" varchar not null,
  "national_id" varchar,
  "address" text not null,
  "suburb" varchar,
  "phone" varchar not null,
  "email" varchar,
  "previous_school" varchar,
  "grade_applying_for" varchar not null,
  "academic_year" varchar not null,
  "guardian_first_name" varchar not null,
  "guardian_surname" varchar not null,
  "guardian_relationship" varchar not null,
  "guardian_phone" varchar not null,
  "guardian_email" varchar,
  "guardian_address" text not null,
  "guardian_employer" varchar,
  "medical_conditions" text,
  "allergies" text,
  "emergency_contact" varchar not null,
  "emergency_phone" varchar not null,
  "birth_certificate" tinyint(1) not null default('0'),
  "report_cards" tinyint(1) not null default('0'),
  "medical_certificate" tinyint(1) not null default('0'),
  "passport_photo" tinyint(1) not null default('0'),
  "status" varchar not null default('pending'),
  "notes" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "student_id" integer,
  foreign key("reviewed_by") references users("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "enrollment_applications_school_id_index" on "enrollment_applications"(
  "school_id"
);
CREATE INDEX "enrollment_applications_school_id_status_index" on "enrollment_applications"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "inventory_items"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "sku" varchar,
  "type" varchar not null default 'uniform',
  "size" varchar,
  "unit_price" numeric not null,
  "currency" varchar not null default 'USD',
  "stock_quantity" integer not null default '0',
  "reorder_level" integer not null default '5',
  "billing_mode" varchar not null default 'direct_sale',
  "is_active" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "inventory_items_school_id_type_index" on "inventory_items"(
  "school_id",
  "type"
);
CREATE UNIQUE INDEX "inventory_items_school_id_sku_unique" on "inventory_items"(
  "school_id",
  "sku"
);
CREATE TABLE IF NOT EXISTS "inventory_sales"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "sale_number" varchar not null,
  "student_id" integer,
  "total_amount" numeric not null,
  "currency" varchar not null default 'USD',
  "payment_method" varchar not null default 'student_account',
  "status" varchar not null default 'completed',
  "invoice_id" integer,
  "sold_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("sold_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "inventory_sales_school_id_sale_number_unique" on "inventory_sales"(
  "school_id",
  "sale_number"
);
CREATE TABLE IF NOT EXISTS "inventory_sale_items"(
  "id" integer primary key autoincrement not null,
  "inventory_sale_id" integer not null,
  "inventory_item_id" integer not null,
  "quantity" integer not null,
  "unit_price" numeric not null,
  "line_total" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("inventory_sale_id") references "inventory_sales"("id") on delete cascade,
  foreign key("inventory_item_id") references "inventory_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "inventory_movements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "inventory_item_id" integer not null,
  "type" varchar not null,
  "quantity_change" integer not null,
  "quantity_after" integer not null,
  "reference_type" varchar,
  "reference_id" integer,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("inventory_item_id") references "inventory_items"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "inventory_movements_inventory_item_id_type_index" on "inventory_movements"(
  "inventory_item_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "holiday_programs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "academic_year" varchar,
  "start_date" date not null,
  "end_date" date not null,
  "fee_amount" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "is_active" tinyint(1) not null default '0',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "holiday_programs_school_id_is_active_index" on "holiday_programs"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "holiday_enrollments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "holiday_program_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "status" varchar not null default 'enrolled',
  "enrolled_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("holiday_program_id") references "holiday_programs"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE UNIQUE INDEX "holiday_enrollments_holiday_program_id_student_id_unique" on "holiday_enrollments"(
  "holiday_program_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "holiday_attendance"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "holiday_program_id" integer not null,
  "student_id" integer not null,
  "date" date not null,
  "status" varchar not null default 'present',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("holiday_program_id") references "holiday_programs"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "holiday_attendance_holiday_program_id_student_id_date_unique" on "holiday_attendance"(
  "holiday_program_id",
  "student_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "parent_notifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "parent_user_id" integer not null,
  "student_id" integer,
  "type" varchar not null,
  "title" varchar not null,
  "body" text not null,
  "data" text,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "parent_notifications_parent_user_id_read_at_index" on "parent_notifications"(
  "parent_user_id",
  "read_at"
);
CREATE INDEX "parent_notifications_school_id_type_index" on "parent_notifications"(
  "school_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "disciplinary_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "incident_date" date not null,
  "category" varchar not null,
  "severity" varchar not null default 'minor',
  "description" text not null,
  "action_taken" text,
  "recorded_by" integer,
  "parent_notified" tinyint(1) not null default '0',
  "parent_notified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE INDEX "disciplinary_records_student_id_incident_date_index" on "disciplinary_records"(
  "student_id",
  "incident_date"
);
CREATE TABLE IF NOT EXISTS "communication_threads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "parent_user_id" integer not null,
  "staff_user_id" integer,
  "subject" varchar not null,
  "status" varchar not null default 'open',
  "last_message_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete set null
);
CREATE INDEX "communication_threads_parent_user_id_status_index" on "communication_threads"(
  "parent_user_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "communication_messages"(
  "id" integer primary key autoincrement not null,
  "thread_id" integer not null,
  "sender_id" integer not null,
  "body" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "read_by_recipient_at" datetime,
  foreign key("thread_id") references "communication_threads"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete cascade
);
CREATE INDEX "communication_messages_thread_id_created_at_index" on "communication_messages"(
  "thread_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "notification_queue"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "guardian_id" integer,
  "channel" varchar not null,
  "recipient_email" varchar,
  "recipient_phone" varchar,
  "subject" text,
  "message" text not null,
  "data" text,
  "status" varchar not null default('pending'),
  "sent_at" datetime,
  "error_message" text,
  "retry_count" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "parent_user_id" integer,
  foreign key("guardian_id") references guardians("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("parent_user_id") references "users"("id") on delete set null
);
CREATE INDEX "notification_queue_notifiable_type_notifiable_id_index" on "notification_queue"(
  "notifiable_type",
  "notifiable_id"
);
CREATE INDEX "notification_queue_school_id_status_created_at_index" on "notification_queue"(
  "school_id",
  "status",
  "created_at"
);
CREATE INDEX "notification_queue_type_status_index" on "notification_queue"(
  "type",
  "status"
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "user_id" integer,
  "module" varchar not null,
  "action" varchar not null,
  "auditable_type" varchar,
  "auditable_id" integer,
  "description" varchar,
  "old_values" text,
  "new_values" text,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "device_type" varchar,
  "platform" varchar,
  "location" varchar,
  "request_method" varchar,
  "request_path" varchar,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  "integrity_hash" varchar,
  "previous_hash" varchar,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "audit_logs_school_id_module_created_at_index" on "audit_logs"(
  "school_id",
  "module",
  "created_at"
);
CREATE INDEX "audit_logs_user_id_created_at_index" on "audit_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "audit_logs_auditable_type_auditable_id_index" on "audit_logs"(
  "auditable_type",
  "auditable_id"
);
CREATE INDEX "audit_logs_action_created_at_index" on "audit_logs"(
  "action",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "login_history"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "user_id" integer,
  "email" varchar,
  "event" varchar not null,
  "ip_address" varchar,
  "user_agent" text,
  "device_type" varchar,
  "platform" varchar,
  "location" varchar,
  "token_name" varchar,
  "failure_reason" text,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "login_history_school_id_event_created_at_index" on "login_history"(
  "school_id",
  "event",
  "created_at"
);
CREATE INDEX "login_history_user_id_created_at_index" on "login_history"(
  "user_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "exam_results"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "marks_obtained" numeric not null,
  "total_marks" numeric not null,
  "percentage" numeric,
  "grade" varchar,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'draft',
  "entered_by" integer,
  "approved_by" integer,
  "approved_at" datetime,
  "is_locked" tinyint(1) not null default '0',
  "locked_at" datetime,
  foreign key("subject_id") references subjects("id") on delete cascade on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("exam_id") references exams("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("entered_by") references "users"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "exam_results_exam_id_student_id_unique" on "exam_results"(
  "exam_id",
  "student_id"
);
CREATE INDEX "exam_results_school_id_exam_id_index" on "exam_results"(
  "school_id",
  "exam_id"
);
CREATE INDEX "exam_results_school_id_student_id_index" on "exam_results"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "exams"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "term_id" integer not null,
  "grade_level_id" integer not null,
  "subject_id" integer not null,
  "name" varchar not null,
  "description" text,
  "exam_date" date not null,
  "start_time" time,
  "end_time" time,
  "total_marks" numeric not null default('100'),
  "passing_marks" numeric,
  "academic_year" varchar not null,
  "is_published" tinyint(1) not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "results_approved_at" datetime,
  "results_approved_by" integer,
  foreign key("subject_id") references subjects("id") on delete cascade on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete cascade on update no action,
  foreign key("term_id") references terms("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("results_approved_by") references "users"("id") on delete set null
);
CREATE INDEX "exams_school_id_academic_year_index" on "exams"(
  "school_id",
  "academic_year"
);
CREATE INDEX "exams_school_id_exam_date_index" on "exams"(
  "school_id",
  "exam_date"
);
CREATE INDEX "exams_school_id_term_id_grade_level_id_index" on "exams"(
  "school_id",
  "term_id",
  "grade_level_id"
);
CREATE TABLE IF NOT EXISTS "workflow_definitions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "code" varchar not null,
  "name" varchar not null,
  "module" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "layout" text,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "workflow_definitions_school_id_code_unique" on "workflow_definitions"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "workflow_definition_steps"(
  "id" integer primary key autoincrement not null,
  "definition_id" integer not null,
  "step_order" integer not null,
  "name" varchar not null,
  "approver_role" varchar,
  "approver_user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "escalation_hours" integer,
  "position" text,
  "approval_mode" varchar not null default 'sequential',
  "required_approvals" integer not null default '1',
  foreign key("definition_id") references "workflow_definitions"("id") on delete cascade,
  foreign key("approver_user_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "workflow_definition_steps_definition_id_step_order_unique" on "workflow_definition_steps"(
  "definition_id",
  "step_order"
);
CREATE TABLE IF NOT EXISTS "workflow_instances"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "definition_id" integer not null,
  "subject_type" varchar not null,
  "subject_id" integer not null,
  "status" varchar not null default 'pending',
  "current_step_order" integer not null default '1',
  "initiated_by" integer not null,
  "metadata" text,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "step_due_at" datetime,
  "escalated_at" datetime,
  "escalation_count" integer not null default '0',
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("definition_id") references "workflow_definitions"("id") on delete cascade,
  foreign key("initiated_by") references "users"("id") on delete cascade
);
CREATE INDEX "workflow_instances_school_id_status_index" on "workflow_instances"(
  "school_id",
  "status"
);
CREATE INDEX "workflow_instances_subject_type_subject_id_index" on "workflow_instances"(
  "subject_type",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "workflow_approvals"(
  "id" integer primary key autoincrement not null,
  "instance_id" integer not null,
  "step_order" integer not null,
  "approver_id" integer,
  "action" varchar not null,
  "comments" text,
  "acted_at" datetime not null default CURRENT_TIMESTAMP,
  "attachments" text,
  "rejection_reason" varchar,
  foreign key("instance_id") references "workflow_instances"("id") on delete cascade,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE INDEX "workflow_approvals_instance_id_step_order_index" on "workflow_approvals"(
  "instance_id",
  "step_order"
);
CREATE TABLE IF NOT EXISTS "vendors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "contact_person" varchar,
  "email" varchar,
  "phone" varchar,
  "address" text,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "purchase_requisition_items"(
  "id" integer primary key autoincrement not null,
  "requisition_id" integer not null,
  "description" varchar not null,
  "quantity" integer not null default '1',
  "unit_cost" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("requisition_id") references "purchase_requisitions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "goods_receipts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "requisition_id" integer not null,
  "vendor_id" integer,
  "received_by" integer not null,
  "received_date" date not null,
  "notes" text,
  "status" varchar not null default 'received',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("requisition_id") references "purchase_requisitions"("id") on delete cascade,
  foreign key("vendor_id") references "vendors"("id") on delete set null,
  foreign key("received_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "assets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "asset_tag" varchar,
  "name" varchar not null,
  "category" varchar not null,
  "purchase_date" date,
  "purchase_cost" numeric,
  "location" varchar,
  "custodian_user_id" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("custodian_user_id") references "users"("id") on delete set null
);
CREATE INDEX "assets_school_id_category_index" on "assets"(
  "school_id",
  "category"
);
CREATE TABLE IF NOT EXISTS "asset_maintenance_logs"(
  "id" integer primary key autoincrement not null,
  "asset_id" integer not null,
  "maintenance_date" date not null,
  "description" text not null,
  "cost" numeric,
  "performed_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("asset_id") references "assets"("id") on delete cascade,
  foreign key("performed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "asset_disposals"(
  "id" integer primary key autoincrement not null,
  "asset_id" integer not null,
  "disposed_at" date not null,
  "reason" text not null,
  "workflow_instance_id" integer,
  "approved_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("asset_id") references "assets"("id") on delete cascade,
  foreign key("workflow_instance_id") references "workflow_instances"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "vehicles"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "registration_number" varchar not null,
  "make" varchar,
  "model" varchar,
  "capacity" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "drivers"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer,
  "name" varchar not null,
  "license_number" varchar,
  "phone" varchar,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "transport_routes"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "vehicle_id" integer,
  "driver_id" integer,
  "route_description" text,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete set null,
  foreign key("driver_id") references "drivers"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_transport_allocations"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "route_id" integer not null,
  "pickup_point" varchar,
  "status" varchar not null default 'active',
  "allocated_at" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("route_id") references "transport_routes"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_transport_allocations_student_id_route_id_unique" on "student_transport_allocations"(
  "student_id",
  "route_id"
);
CREATE TABLE IF NOT EXISTS "hostels"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "gender" varchar,
  "capacity" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "hostel_rooms"(
  "id" integer primary key autoincrement not null,
  "hostel_id" integer not null,
  "room_number" varchar not null,
  "capacity" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("hostel_id") references "hostels"("id") on delete cascade
);
CREATE UNIQUE INDEX "hostel_rooms_hostel_id_room_number_unique" on "hostel_rooms"(
  "hostel_id",
  "room_number"
);
CREATE TABLE IF NOT EXISTS "hostel_beds"(
  "id" integer primary key autoincrement not null,
  "room_id" integer not null,
  "bed_number" varchar not null,
  "status" varchar not null default 'available',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("room_id") references "hostel_rooms"("id") on delete cascade
);
CREATE UNIQUE INDEX "hostel_beds_room_id_bed_number_unique" on "hostel_beds"(
  "room_id",
  "bed_number"
);
CREATE TABLE IF NOT EXISTS "hostel_allocations"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "bed_id" integer not null,
  "allocated_at" date not null,
  "left_at" date,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("bed_id") references "hostel_beds"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "library_books"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "isbn" varchar,
  "title" varchar not null,
  "author" varchar,
  "total_copies" integer not null default '1',
  "available_copies" integer not null default '1',
  "category" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "rack_number" varchar,
  "shelf_number" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "visitors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "phone" varchar,
  "id_number" varchar,
  "purpose" varchar not null,
  "host_user_id" integer,
  "student_id" integer,
  "check_in_at" datetime,
  "check_out_at" datetime,
  "status" varchar not null default 'checked_in',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("host_user_id") references "users"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_medical_profiles"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "conditions" text,
  "allergies" text,
  "blood_group" varchar,
  "emergency_notes" text,
  "medications" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_medical_profiles_student_id_unique" on "student_medical_profiles"(
  "student_id"
);
CREATE TABLE IF NOT EXISTS "clinic_visits"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "visit_date" date not null,
  "complaint" text not null,
  "diagnosis" text,
  "treatment" text,
  "nurse_user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("nurse_user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "type" varchar not null default 'general',
  "starts_at" datetime not null,
  "ends_at" datetime,
  "location" varchar,
  "description" text,
  "status" varchar not null default 'scheduled',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "category" varchar,
  "content" text not null,
  "effective_date" date,
  "review_date" date,
  "status" varchar not null default 'active',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "incident_reports"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "reported_by" integer not null,
  "category" varchar not null,
  "severity" varchar not null default 'low',
  "description" text not null,
  "status" varchar not null default 'open',
  "workflow_instance_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("reported_by") references "users"("id") on delete cascade,
  foreign key("workflow_instance_id") references "workflow_instances"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "consent_forms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "content" text not null,
  "target_audience" varchar not null default 'parents',
  "due_date" date,
  "requires_signature" tinyint(1) not null default '0',
  "status" varchar not null default 'active',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "consent_responses"(
  "id" integer primary key autoincrement not null,
  "form_id" integer not null,
  "parent_user_id" integer not null,
  "student_id" integer,
  "status" varchar not null default 'pending',
  "responded_at" datetime,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("form_id") references "consent_forms"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE UNIQUE INDEX "consent_responses_form_id_parent_user_id_student_id_unique" on "consent_responses"(
  "form_id",
  "parent_user_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "schools"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "code" varchar not null,
  "address" text,
  "phone" varchar,
  "email" varchar,
  "status" varchar not null default('active'),
  "contact_person" varchar,
  "contact_phone" varchar,
  "contact_email" varchar,
  "currency" varchar not null default('USD'),
  "currency_default" varchar not null default('USD'),
  "currency_locked" tinyint(1) not null default('0'),
  "academic_year" varchar,
  "current_term" varchar,
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime,
  "parent_school_id" integer,
  "branch_type" varchar,
  "license_status" varchar not null default 'none',
  "license_plan" varchar,
  "license_expires_at" datetime,
  "principal_name" varchar,
  "website" varchar,
  "year_founded" integer,
  "suburb" varchar,
  "city" varchar,
  "student_capacity" integer,
  "timezone" varchar,
  "motto" varchar,
  "logo_path" varchar,
  foreign key("parent_school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "schools_code_unique" on "schools"("code");
CREATE INDEX "schools_status_index" on "schools"("status");
CREATE INDEX "schools_parent_school_id_index" on "schools"("parent_school_id");
CREATE TABLE IF NOT EXISTS "policy_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "module" varchar not null,
  "trigger_event" varchar not null,
  "conditions" text not null,
  "actions" text not null,
  "priority" integer not null default '100',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "policy_rules_school_id_code_unique" on "policy_rules"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "hub_messages"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "sender_id" integer not null,
  "subject" varchar,
  "body" text not null,
  "channels" text not null,
  "audience_type" varchar not null default 'individual',
  "audience_ids" text,
  "status" varchar not null default 'queued',
  "sent_at" datetime,
  "delivery_stats" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete cascade
);
CREATE INDEX "hub_messages_school_id_status_index" on "hub_messages"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "hub_message_deliveries"(
  "id" integer primary key autoincrement not null,
  "hub_message_id" integer not null,
  "recipient_user_id" integer,
  "channel" varchar not null,
  "recipient_address" varchar,
  "status" varchar not null default 'pending',
  "delivered_at" datetime,
  "read_at" datetime,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("hub_message_id") references "hub_messages"("id") on delete cascade,
  foreign key("recipient_user_id") references "users"("id") on delete set null
);
CREATE INDEX "hub_message_deliveries_hub_message_id_channel_index" on "hub_message_deliveries"(
  "hub_message_id",
  "channel"
);
CREATE INDEX "hub_message_deliveries_recipient_user_id_read_at_index" on "hub_message_deliveries"(
  "recipient_user_id",
  "read_at"
);
CREATE TABLE IF NOT EXISTS "signable_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "document_type" varchar not null,
  "content" text not null,
  "status" varchar not null default 'draft',
  "created_by" integer not null,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "document_signatures"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "signer_id" integer not null,
  "signer_role" varchar,
  "signature_hash" varchar not null,
  "ip_address" varchar,
  "signed_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("document_id") references "signable_documents"("id") on delete cascade,
  foreign key("signer_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_signatures_document_id_signer_id_unique" on "document_signatures"(
  "document_id",
  "signer_id"
);
CREATE TABLE IF NOT EXISTS "scholarships"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "type" varchar not null,
  "amount" numeric,
  "percentage" numeric,
  "criteria" text,
  "application_deadline" date,
  "slots" integer,
  "status" varchar not null default 'open',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "scholarship_applications"(
  "id" integer primary key autoincrement not null,
  "scholarship_id" integer not null,
  "student_id" integer not null,
  "school_id" integer not null,
  "motivation" text,
  "supporting_data" text,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("scholarship_id") references "scholarships"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "scholarship_applications_scholarship_id_student_id_unique" on "scholarship_applications"(
  "scholarship_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "payment_gateway_configs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "provider" varchar not null,
  "credentials" text not null,
  "is_active" tinyint(1) not null default '0',
  "supports_cards" tinyint(1) not null default '1',
  "supports_mobile_money" tinyint(1) not null default '0',
  "supports_bank_transfer" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "payment_gateway_configs_school_id_provider_unique" on "payment_gateway_configs"(
  "school_id",
  "provider"
);
CREATE TABLE IF NOT EXISTS "payment_gateway_transactions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "config_id" integer not null,
  "invoice_id" integer,
  "student_id" integer,
  "provider_reference" varchar,
  "internal_reference" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "payment_method" varchar not null,
  "status" varchar not null default 'initiated',
  "provider_response" text,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("config_id") references "payment_gateway_configs"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "payment_gateway_transactions_school_id_status_index" on "payment_gateway_transactions"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "payment_gateway_transactions_internal_reference_unique" on "payment_gateway_transactions"(
  "internal_reference"
);
CREATE TABLE IF NOT EXISTS "fee_penalty_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "grace_days" integer not null default '0',
  "penalty_type" varchar not null,
  "penalty_value" numeric not null,
  "frequency" varchar not null default 'once',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "fee_penalties"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "rule_id" integer not null,
  "invoice_id" integer not null,
  "student_id" integer not null,
  "amount" numeric not null,
  "applied_on" date not null,
  "status" varchar not null default 'applied',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("rule_id") references "fee_penalty_rules"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE INDEX "fee_penalties_invoice_id_applied_on_index" on "fee_penalties"(
  "invoice_id",
  "applied_on"
);
CREATE TABLE IF NOT EXISTS "refunds"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "payment_id" integer not null,
  "student_id" integer not null,
  "amount" numeric not null,
  "reason" varchar not null,
  "status" varchar not null default 'pending',
  "requested_by" integer not null,
  "approved_by" integer,
  "processed_at" datetime,
  "reference" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("payment_id") references "payments"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("requested_by") references "users"("id") on delete cascade,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "secure_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vault_type" varchar not null default 'exam_paper',
  "title" varchar not null,
  "encrypted_payload" text not null,
  "content_hash" varchar not null,
  "access_roles" text not null,
  "uploaded_by" integer not null,
  "exam_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete set null
);
CREATE INDEX "secure_documents_school_id_vault_type_index" on "secure_documents"(
  "school_id",
  "vault_type"
);
CREATE TABLE IF NOT EXISTS "secure_document_access_logs"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "user_id" integer not null,
  "action" varchar not null,
  "ip_address" varchar,
  "accessed_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("document_id") references "secure_documents"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "behavior_points"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "points" integer not null,
  "category" varchar not null,
  "description" text not null,
  "recorded_by" integer not null,
  "recorded_on" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete cascade
);
CREATE INDEX "behavior_points_student_id_recorded_on_index" on "behavior_points"(
  "student_id",
  "recorded_on"
);
CREATE TABLE IF NOT EXISTS "student_interventions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "intervention_type" varchar not null,
  "status" varchar not null default 'open',
  "summary" text not null,
  "action_plan" text,
  "assigned_to" integer,
  "created_by" integer not null,
  "start_date" date not null,
  "follow_up_date" date,
  "closed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_tasks"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "description" text,
  "assigned_to" integer not null,
  "assigned_by" integer,
  "priority" varchar not null default 'normal',
  "status" varchar not null default 'pending',
  "due_date" date,
  "progress_percent" integer not null default '0',
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete cascade,
  foreign key("assigned_by") references "users"("id") on delete set null
);
CREATE INDEX "staff_tasks_assigned_to_status_index" on "staff_tasks"(
  "assigned_to",
  "status"
);
CREATE TABLE IF NOT EXISTS "staff_feed_posts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "author_id" integer not null,
  "body" text not null,
  "attachments" text,
  "visibility" varchar not null default 'staff',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_feed_comments"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "author_id" integer not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "staff_feed_posts"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "operations_alerts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "severity" varchar not null,
  "category" varchar not null,
  "title" varchar not null,
  "message" text not null,
  "metadata" text,
  "resolved_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "operations_alerts_school_id_resolved_at_index" on "operations_alerts"(
  "school_id",
  "resolved_at"
);
CREATE TABLE IF NOT EXISTS "retention_policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "module" varchar not null,
  "retain_years" integer not null,
  "archive_action" varchar not null default 'archive',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "retention_policies_school_id_module_unique" on "retention_policies"(
  "school_id",
  "module"
);
CREATE TABLE IF NOT EXISTS "archived_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "source_table" varchar not null,
  "source_id" integer not null,
  "snapshot" text not null,
  "archived_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "archived_records_school_id_source_table_index" on "archived_records"(
  "school_id",
  "source_table"
);
CREATE TABLE IF NOT EXISTS "data_masking_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "module" varchar not null,
  "field" varchar not null,
  "visible_roles" text not null,
  "mask_pattern" varchar not null default '***',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "data_masking_rules_school_id_module_field_unique" on "data_masking_rules"(
  "school_id",
  "module",
  "field"
);
CREATE TABLE IF NOT EXISTS "api_clients"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "client_id" varchar not null,
  "client_secret_hash" varchar not null,
  "scopes" text not null,
  "is_active" tinyint(1) not null default '1',
  "last_used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "api_clients_client_id_unique" on "api_clients"(
  "client_id"
);
CREATE TABLE IF NOT EXISTS "record_versions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "versionable_type" varchar not null,
  "versionable_id" integer not null,
  "version_number" integer not null,
  "snapshot" text not null,
  "changed_by" integer,
  "change_reason" varchar,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE INDEX "record_versions_versionable_type_versionable_id_version_number_index" on "record_versions"(
  "versionable_type",
  "versionable_id",
  "version_number"
);
CREATE TABLE IF NOT EXISTS "curriculum_versions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer not null,
  "term_id" integer,
  "academic_year" varchar not null,
  "version_number" integer not null default '1',
  "syllabus" text,
  "learning_outcomes" text,
  "status" varchar not null default 'draft',
  "published_by" integer,
  "published_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null,
  foreign key("published_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "curriculum_versions_school_id_subject_id_academic_year_version_number_unique" on "curriculum_versions"(
  "school_id",
  "subject_id",
  "academic_year",
  "version_number"
);
CREATE TABLE IF NOT EXISTS "learning_outcome_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "outcome_code" varchar not null,
  "competency_level" varchar not null,
  "score" numeric,
  "evidence" text,
  "assessed_by" integer,
  "assessed_on" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("assessed_by") references "users"("id") on delete set null
);
CREATE INDEX "learning_outcome_records_student_id_subject_id_index" on "learning_outcome_records"(
  "student_id",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "assessment_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "name" varchar not null,
  "type" varchar not null,
  "weight" numeric not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "continuous_assessments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "category_id" integer not null,
  "term_id" integer,
  "title" varchar not null,
  "score" numeric not null,
  "max_score" numeric not null default '100',
  "assessed_on" date not null,
  "recorded_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("category_id") references "assessment_categories"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "gradebook_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "grade_level_id" integer,
  "category_weights" text not null,
  "pass_mark" numeric not null default '50',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("grade_level_id") references "grade_levels"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "subject_prerequisites"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer not null,
  "prerequisite_subject_id" integer not null,
  "minimum_grade" numeric not null default '50',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("prerequisite_subject_id") references "subjects"("id") on delete cascade
);
CREATE UNIQUE INDEX "subject_prerequisites_subject_id_prerequisite_subject_id_unique" on "subject_prerequisites"(
  "subject_id",
  "prerequisite_subject_id"
);
CREATE TABLE IF NOT EXISTS "promotion_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "conditions" text not null,
  "action" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "academic_calendar_entries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "term_id" integer,
  "entry_type" varchar not null,
  "title" varchar not null,
  "start_date" date not null,
  "end_date" date,
  "is_holiday" tinyint(1) not null default '0',
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "chart_of_accounts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "account_type" varchar not null,
  "parent_id" integer,
  "normal_balance" varchar not null default 'debit',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_id") references "chart_of_accounts"("id") on delete set null
);
CREATE UNIQUE INDEX "chart_of_accounts_school_id_code_unique" on "chart_of_accounts"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "journal_entries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "reference" varchar not null,
  "entry_date" date not null,
  "description" text not null,
  "status" varchar not null default 'posted',
  "currency" varchar not null default 'USD',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "journal_entries_reference_unique" on "journal_entries"(
  "reference"
);
CREATE TABLE IF NOT EXISTS "journal_lines"(
  "id" integer primary key autoincrement not null,
  "journal_entry_id" integer not null,
  "account_id" integer not null,
  "debit" numeric not null default '0',
  "credit" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "memo" text,
  foreign key("journal_entry_id") references "journal_entries"("id") on delete cascade,
  foreign key("account_id") references "chart_of_accounts"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exchange_rates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "from_currency" varchar not null,
  "to_currency" varchar not null,
  "rate" numeric not null,
  "effective_date" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "exchange_rates_school_id_from_currency_to_currency_effective_date_unique" on "exchange_rates"(
  "school_id",
  "from_currency",
  "to_currency",
  "effective_date"
);
CREATE TABLE IF NOT EXISTS "instalment_plans"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "total_amount" numeric not null,
  "currency" varchar not null default 'USD',
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "instalment_schedule_items"(
  "id" integer primary key autoincrement not null,
  "plan_id" integer not null,
  "installment_number" integer not null,
  "due_date" date not null,
  "amount" numeric not null,
  "amount_paid" numeric not null default '0',
  "status" varchar not null default 'pending',
  "paid_at" datetime,
  foreign key("plan_id") references "instalment_plans"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "bank_statement_lines"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "transaction_date" date not null,
  "reference" varchar,
  "description" text,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "matched_payment_id" integer,
  "match_status" varchar not null default 'unmatched',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("matched_payment_id") references "payments"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "revenue_recognition_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "recognition_method" varchar not null,
  "config" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "workflow_delegations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "delegator_id" integer not null,
  "delegate_id" integer not null,
  "starts_on" date not null,
  "ends_on" date not null,
  "scope" varchar not null default 'all_workflows',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("delegator_id") references "users"("id") on delete cascade,
  foreign key("delegate_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_contracts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "contract_type" varchar not null,
  "start_date" date not null,
  "end_date" date,
  "salary" numeric,
  "status" varchar not null default 'active',
  "renewal_due_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "performance_reviews"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "staff_user_id" integer not null,
  "reviewer_id" integer,
  "period" varchar not null,
  "overall_score" numeric,
  "summary" text,
  "criteria_scores" text,
  "status" varchar not null default 'draft',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete cascade,
  foreign key("reviewer_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "staff_certifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "certification_name" varchar not null,
  "issuer" varchar,
  "issued_on" date,
  "expires_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_disciplinary_cases"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "staff_user_id" integer not null,
  "category" varchar not null,
  "description" text not null,
  "outcome" text,
  "status" varchar not null default 'open',
  "recorded_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_shifts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "shift_date" date not null,
  "start_time" time not null,
  "end_time" time not null,
  "location" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "admission_scores"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "enrollment_application_id" integer,
  "applicant_name" varchar not null,
  "academic_score" numeric not null default '0',
  "interview_score" numeric not null default '0',
  "total_score" numeric not null default '0',
  "recommendation" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("enrollment_application_id") references "enrollment_applications"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "alumni_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "full_name" varchar not null,
  "graduation_year" varchar not null,
  "email" varchar,
  "phone" varchar,
  "current_occupation" varchar,
  "engagement_history" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_timeline_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "event_type" varchar not null,
  "title" varchar not null,
  "description" text,
  "metadata" text,
  "occurred_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE INDEX "student_timeline_events_student_id_occurred_at_index" on "student_timeline_events"(
  "student_id",
  "occurred_at"
);
CREATE TABLE IF NOT EXISTS "question_bank_items"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "question_text" text not null,
  "question_type" varchar not null default 'multiple_choice',
  "options" text,
  "correct_answer" varchar,
  "difficulty" varchar not null default 'medium',
  "tags" text,
  "marks" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "cbt_exam_sessions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer,
  "student_id" integer not null,
  "question_ids" text not null,
  "started_at" datetime,
  "submitted_at" datetime,
  "score" numeric,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "cbt_responses"(
  "id" integer primary key autoincrement not null,
  "session_id" integer not null,
  "question_id" integer not null,
  "answer" text,
  "is_correct" tinyint(1),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("session_id") references "cbt_exam_sessions"("id") on delete cascade,
  foreign key("question_id") references "question_bank_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exam_anti_cheat_logs"(
  "id" integer primary key autoincrement not null,
  "session_id" integer not null,
  "event_type" varchar not null,
  "metadata" text,
  "logged_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("session_id") references "cbt_exam_sessions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "remark_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_result_id" integer not null,
  "student_id" integer not null,
  "reason" text not null,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_result_id") references "exam_results"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "message_campaigns"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "channels" text not null,
  "audience_filter" text not null,
  "message_body" text not null,
  "scheduled_at" datetime,
  "sent_at" datetime,
  "status" varchar not null default 'draft',
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "communication_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "channel_preferences" text not null,
  "topic_preferences" text,
  "marketing_opt_in" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "communication_preferences_user_id_unique" on "communication_preferences"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "parent_engagement_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "parent_user_id" integer not null,
  "event_type" varchar not null,
  "title" varchar not null,
  "attended_at" datetime,
  "attended" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "maintenance_schedules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "asset_type" varchar not null,
  "asset_id" integer,
  "title" varchar not null,
  "frequency" varchar not null,
  "next_due_date" date not null,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "facility_tasks"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "task_type" varchar not null,
  "location" varchar not null,
  "scheduled_date" date not null,
  "assigned_to" integer,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "room_utilization_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "room_id" integer not null,
  "log_date" date not null,
  "hours_used" integer not null default '0',
  "capacity" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("room_id") references "rooms"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "transport_route_stops"(
  "id" integer primary key autoincrement not null,
  "route_id" integer not null,
  "stop_name" varchar not null,
  "latitude" numeric,
  "longitude" numeric,
  "stop_order" integer not null default '1',
  "pickup_time" time,
  foreign key("route_id") references "transport_routes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "gps_tracking_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vehicle_id" integer not null,
  "latitude" numeric not null,
  "longitude" numeric not null,
  "speed" numeric,
  "recorded_at" datetime not null,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "fuel_consumption_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vehicle_id" integer not null,
  "log_date" date not null,
  "litres" numeric not null,
  "cost" numeric,
  "odometer" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "transport_trips"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "route_id" integer not null,
  "vehicle_id" integer,
  "trip_date" date not null,
  "departure_time" time,
  "arrival_time" time,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("route_id") references "transport_routes"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "library_reservations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "book_id" integer not null,
  "student_id" integer,
  "user_id" integer,
  "reserved_at" datetime not null,
  "expires_at" datetime,
  "status" varchar not null default 'queued',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("book_id") references "library_books"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "digital_library_resources"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "resource_type" varchar not null,
  "file_path" varchar,
  "isbn" varchar,
  "access_roles" text,
  "view_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "library_fines"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "loan_id" integer not null,
  "amount" numeric not null,
  "status" varchar not null default 'pending',
  "paid_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("loan_id") references "library_loans"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "compliance_requirements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "authority" varchar not null,
  "requirement_code" varchar not null,
  "title" varchar not null,
  "due_date" date,
  "status" varchar not null default 'pending',
  "evidence" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "integration_connectors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "provider" varchar not null,
  "connector_type" varchar not null,
  "config" text not null,
  "is_active" tinyint(1) not null default '0',
  "last_sync_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "integration_connectors_school_id_provider_connector_type_unique" on "integration_connectors"(
  "school_id",
  "provider",
  "connector_type"
);
CREATE TABLE IF NOT EXISTS "abac_policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "resource" varchar not null,
  "conditions" text not null,
  "effect" varchar not null default 'allow',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "device_sessions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "device_name" varchar,
  "ip_address" varchar,
  "user_agent" text,
  "last_active_at" datetime,
  "revoked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "group_policies"(
  "id" integer primary key autoincrement not null,
  "parent_school_id" integer not null,
  "policy_code" varchar not null,
  "name" varchar not null,
  "rules" text not null,
  "enforce_on_branches" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "group_policies_parent_school_id_policy_code_unique" on "group_policies"(
  "parent_school_id",
  "policy_code"
);
CREATE TABLE IF NOT EXISTS "cross_school_transfers"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "from_school_id" integer not null,
  "to_school_id" integer not null,
  "status" varchar not null default 'pending',
  "requested_by" integer not null,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("from_school_id") references "schools"("id") on delete cascade,
  foreign key("to_school_id") references "schools"("id") on delete cascade,
  foreign key("requested_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "shared_teacher_assignments"(
  "id" integer primary key autoincrement not null,
  "teacher_id" integer not null,
  "school_id" integer not null,
  "allocation_percent" numeric not null default '100',
  "starts_on" date not null,
  "ends_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "license_keys"(
  "id" integer primary key autoincrement not null,
  "key_prefix" varchar not null,
  "key_hash" varchar not null,
  "plan_type" varchar not null,
  "duration_months" integer,
  "status" varchar not null default 'unused',
  "school_id" integer,
  "activated_at" datetime,
  "expires_at" datetime,
  "revoked_at" datetime,
  "customer_name" varchar,
  "customer_email" varchar,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "amount" numeric,
  "currency" varchar not null default 'USD',
  "paid_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "license_keys_school_id_status_index" on "license_keys"(
  "school_id",
  "status"
);
CREATE INDEX "license_keys_status_expires_at_index" on "license_keys"(
  "status",
  "expires_at"
);
CREATE INDEX "license_keys_key_prefix_index" on "license_keys"("key_prefix");
CREATE UNIQUE INDEX "license_keys_key_hash_unique" on "license_keys"(
  "key_hash"
);
CREATE INDEX "payments_school_date_status_idx" on "payments"(
  "school_id",
  "date",
  "status"
);
CREATE INDEX "payments_school_currency_status_idx" on "payments"(
  "school_id",
  "currency",
  "status"
);
CREATE INDEX "payments_school_student_date_idx" on "payments"(
  "school_id",
  "student_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "purchase_requisitions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "requested_by" integer not null,
  "department_id" integer,
  "title" varchar not null,
  "description" text,
  "estimated_cost" numeric not null default('0'),
  "status" varchar not null default('draft'),
  "workflow_instance_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "spend_type" varchar not null default 'procurement',
  "vendor_id" integer,
  "amount_paid" numeric,
  "payment_method" varchar,
  "payment_reference" varchar,
  "disbursed_at" datetime,
  "disbursed_by" integer,
  "transaction_id" integer,
  foreign key("workflow_instance_id") references workflow_instances("id") on delete set null on update no action,
  foreign key("department_id") references departments("id") on delete set null on update no action,
  foreign key("requested_by") references users("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("vendor_id") references "vendors"("id") on delete set null,
  foreign key("disbursed_by") references "users"("id") on delete set null,
  foreign key("transaction_id") references "transactions"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_trips"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "destination" varchar,
  "trip_date" date not null,
  "return_date" date,
  "fee_amount" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "capacity" integer,
  "is_active" tinyint(1) not null default '1',
  "open_for_registration" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "school_trips_school_id_is_active_index" on "school_trips"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "school_trip_enrollments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "school_trip_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "status" varchar not null default 'enrolled',
  "enrolled_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("school_trip_id") references "school_trips"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE UNIQUE INDEX "school_trip_enrollments_school_trip_id_student_id_unique" on "school_trip_enrollments"(
  "school_trip_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "streams"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "streams_school_id_name_unique" on "streams"(
  "school_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "houses"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "color" varchar,
  "teacher_id" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "houses_school_id_name_unique" on "houses"(
  "school_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "classes"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "name" varchar not null,
  "form" varchar,
  "school" varchar,
  "capacity" integer not null default('40'),
  "current_enrollment" integer not null default('0'),
  "teacher_id" integer,
  "grade_level_id" integer,
  "room_id" integer,
  "status" varchar not null default('active'),
  "created_at" datetime,
  "updated_at" datetime,
  "stream_id" integer,
  foreign key("room_id") references rooms("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("stream_id") references "streams"("id") on delete set null
);
CREATE INDEX "classes_school_id_index" on "classes"("school_id");
CREATE UNIQUE INDEX "classes_school_id_name_unique" on "classes"(
  "school_id",
  "name"
);
CREATE INDEX "classes_teacher_id_index" on "classes"("teacher_id");
CREATE TABLE IF NOT EXISTS "enrollments"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "class_id" integer not null,
  "academic_year" varchar not null,
  "enrolled_at" date not null,
  "left_at" date,
  "status" varchar not null default('active'),
  "school_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "stream_id" integer,
  "house_id" integer,
  "reason" varchar,
  "changed_by" integer,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete cascade on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("stream_id") references "streams"("id") on delete set null,
  foreign key("house_id") references "houses"("id") on delete set null,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE INDEX "enrollments_class_id_academic_year_index" on "enrollments"(
  "class_id",
  "academic_year"
);
CREATE INDEX "enrollments_student_id_academic_year_index" on "enrollments"(
  "student_id",
  "academic_year"
);
CREATE UNIQUE INDEX "enrollments_student_id_class_id_academic_year_unique" on "enrollments"(
  "student_id",
  "class_id",
  "academic_year"
);
CREATE TABLE IF NOT EXISTS "grade_level_subjects"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "grade_level_id" integer not null,
  "subject_id" integer not null,
  "stream_id" integer,
  "is_core" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("grade_level_id") references "grade_levels"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("stream_id") references "streams"("id") on delete set null
);
CREATE UNIQUE INDEX "grade_stream_subject_unique" on "grade_level_subjects"(
  "grade_level_id",
  "subject_id",
  "stream_id"
);
CREATE TABLE IF NOT EXISTS "student_status_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "from_status" varchar,
  "to_status" varchar not null,
  "action" varchar not null,
  "reason" text,
  "effective_date" date,
  "performed_by" integer,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("performed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "fee_structures"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "class_id" integer,
  "class_name" varchar not null,
  "category" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default('USD'),
  "created_at" datetime,
  "updated_at" datetime,
  "fee_category_id" integer,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("fee_category_id") references "fee_categories"("id") on delete set null
);
CREATE INDEX "fee_structures_class_id_index" on "fee_structures"("class_id");
CREATE INDEX "fee_structures_school_id_index" on "fee_structures"("school_id");
CREATE TABLE IF NOT EXISTS "attendance"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "class_id" integer,
  "teacher_id" integer,
  "subject_id" integer,
  "lesson_type" varchar,
  "date" date not null,
  "status" varchar not null,
  "time_in" time,
  "time_out" time,
  "remarks" text,
  "parent_notified" tinyint(1) not null default('0'),
  "notification_sent_at" datetime,
  "marked_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "submitted_at" datetime,
  "locked_at" datetime,
  "locked_by" integer,
  foreign key("subject_id") references subjects("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("marked_by") references users("id") on delete set null on update no action,
  foreign key("locked_by") references "users"("id") on delete set null
);
CREATE INDEX "attendance_class_id_date_index" on "attendance"(
  "class_id",
  "date"
);
CREATE INDEX "attendance_school_id_index" on "attendance"("school_id");
CREATE UNIQUE INDEX "attendance_student_id_date_class_id_unique" on "attendance"(
  "student_id",
  "date",
  "class_id"
);
CREATE TABLE IF NOT EXISTS "attendance_sessions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer,
  "teacher_id" integer,
  "date" date not null,
  "period" varchar,
  "submitted_at" datetime,
  "locked_at" datetime,
  "locked_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("teacher_id") references "teachers"("id") on delete set null,
  foreign key("locked_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "attendance_sessions_unique" on "attendance_sessions"(
  "school_id",
  "class_id",
  "date",
  "subject_id",
  "period"
);
CREATE TABLE IF NOT EXISTS "lesson_plans"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "title" varchar not null,
  "plan_type" varchar not null default 'lesson',
  "planned_date" date,
  "topic" varchar,
  "objectives" text,
  "outcomes" text,
  "activities" text,
  "resources" text,
  "status" varchar not null default 'draft',
  "completed_at" datetime,
  "submitted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "syllabus_topics"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "subject_id" integer not null,
  "class_id" integer,
  "title" varchar not null,
  "chapter" varchar,
  "sort_order" integer not null default '0',
  "objectives" text,
  "status" varchar not null default 'planned',
  "coverage_percent" integer not null default '0',
  "completed_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "teaching_resources"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "subject_id" integer,
  "class_id" integer,
  "title" varchar not null,
  "resource_type" varchar not null default 'notes',
  "topic" varchar,
  "term" varchar,
  "file_url" varchar,
  "description" text,
  "shared" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "assignment_submissions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "assignment_id" integer not null,
  "student_id" integer not null,
  "status" varchar not null default 'submitted',
  "file_url" varchar,
  "content" text,
  "score" numeric,
  "teacher_comment" text,
  "submitted_at" datetime,
  "graded_at" datetime,
  "returned_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assignment_id") references "assignments"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "assignment_submissions_assignment_id_student_id_unique" on "assignment_submissions"(
  "assignment_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "online_lessons"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "title" varchar not null,
  "lesson_type" varchar not null default 'live',
  "scheduled_at" datetime,
  "meeting_url" varchar,
  "recording_url" varchar,
  "description" text,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  "provider" varchar not null default 'zoom',
  "meeting_id" varchar,
  "passcode" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "report_card_narratives"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "student_id" integer not null,
  "class_id" integer,
  "term_id" integer,
  "academic_comment" text,
  "behaviour_comment" text,
  "recommendations" text,
  "strengths" text,
  "areas_for_improvement" text,
  "status" varchar not null default 'draft',
  "submitted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE UNIQUE INDEX "report_card_narratives_unique" on "report_card_narratives"(
  "teacher_id",
  "student_id",
  "term_id"
);
CREATE TABLE IF NOT EXISTS "timetable_change_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "request_type" varchar not null default 'change',
  "details" text not null,
  "preferred_slot" varchar,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "review_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "class_substitutions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer,
  "absent_teacher_id" integer not null,
  "substitute_teacher_id" integer,
  "leave_request_id" integer,
  "date" date not null,
  "period" varchar,
  "status" varchar not null default 'open',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("absent_teacher_id") references "teachers"("id") on delete cascade,
  foreign key("substitute_teacher_id") references "teachers"("id") on delete set null,
  foreign key("leave_request_id") references "leave_requests"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "class_participation_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "student_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "recorded_on" date not null,
  "score" integer not null default '3',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "teacher_notifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "type" varchar not null default 'info',
  "title" varchar not null,
  "body" text,
  "link" varchar,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "teacher_notifications_user_id_read_at_index" on "teacher_notifications"(
  "user_id",
  "read_at"
);
CREATE TABLE IF NOT EXISTS "fee_groups"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_groups_school_id_name_unique" on "fee_groups"(
  "school_id",
  "name"
);
CREATE INDEX "fee_groups_school_id_index" on "fee_groups"("school_id");
CREATE TABLE IF NOT EXISTS "fee_group_items"(
  "id" integer primary key autoincrement not null,
  "fee_group_id" integer not null,
  "fee_category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("fee_group_id") references "fee_groups"("id") on delete cascade,
  foreign key("fee_category_id") references "fee_categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_group_items_fee_group_id_fee_category_id_unique" on "fee_group_items"(
  "fee_group_id",
  "fee_category_id"
);
CREATE TABLE IF NOT EXISTS "student_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_categories_school_id_name_unique" on "student_categories"(
  "school_id",
  "name"
);
CREATE INDEX "student_categories_school_id_index" on "student_categories"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "fee_discounts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "discount_type" varchar not null,
  "value" numeric not null,
  "fee_category_id" integer,
  "student_category_id" integer,
  "starts_on" date,
  "ends_on" date,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("fee_category_id") references "fee_categories"("id") on delete set null,
  foreign key("student_category_id") references "student_categories"("id") on delete set null
);
CREATE INDEX "fee_discounts_school_id_is_active_index" on "fee_discounts"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "designations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "designations_school_id_name_unique" on "designations"(
  "school_id",
  "name"
);
CREATE INDEX "designations_school_id_index" on "designations"("school_id");
CREATE TABLE IF NOT EXISTS "employees"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "employee_number" varchar,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "designation_id" integer,
  "department_id" integer,
  "joining_date" date,
  "employment_type" varchar not null default 'full_time',
  "status" varchar not null default 'active',
  "base_salary" numeric,
  "salary_currency" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("designation_id") references "designations"("id") on delete set null,
  foreign key("department_id") references "departments"("id") on delete set null
);
CREATE INDEX "employees_school_id_status_index" on "employees"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "employees_school_id_employee_number_unique" on "employees"(
  "school_id",
  "employee_number"
);
CREATE TABLE IF NOT EXISTS "staff_attendances"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "date" date not null,
  "staff_type" varchar not null,
  "staff_id" integer not null,
  "status" varchar not null,
  "remarks" varchar,
  "recorded_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "staff_attendance_unique" on "staff_attendances"(
  "school_id",
  "date",
  "staff_type",
  "staff_id"
);
CREATE INDEX "staff_attendances_school_id_date_index" on "staff_attendances"(
  "school_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "leave_types"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "default_days" integer,
  "is_paid" tinyint(1) not null default '1',
  "is_active" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "leave_types_school_id_name_unique" on "leave_types"(
  "school_id",
  "name"
);
CREATE INDEX "leave_types_school_id_index" on "leave_types"("school_id");
CREATE TABLE IF NOT EXISTS "certificate_templates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "certificate_type" varchar not null,
  "title" varchar not null,
  "body_html" text not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "certificate_templates_school_id_name_unique" on "certificate_templates"(
  "school_id",
  "name"
);
CREATE INDEX "certificate_templates_school_id_index" on "certificate_templates"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "exam_schedules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "room_id" integer,
  "starts_at" datetime not null,
  "ends_at" datetime,
  "duration_minutes" integer,
  "invigilator" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("room_id") references "rooms"("id") on delete set null
);
CREATE INDEX "exam_schedules_school_id_exam_id_index" on "exam_schedules"(
  "school_id",
  "exam_id"
);
CREATE INDEX "exam_schedules_school_id_starts_at_index" on "exam_schedules"(
  "school_id",
  "starts_at"
);
CREATE TABLE IF NOT EXISTS "income_heads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "income_heads_school_id_name_unique" on "income_heads"(
  "school_id",
  "name"
);
CREATE INDEX "income_heads_school_id_index" on "income_heads"("school_id");
CREATE TABLE IF NOT EXISTS "expense_heads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "expense_heads_school_id_name_unique" on "expense_heads"(
  "school_id",
  "name"
);
CREATE INDEX "expense_heads_school_id_index" on "expense_heads"("school_id");
CREATE TABLE IF NOT EXISTS "library_members"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "member_type" varchar not null,
  "member_id" integer,
  "member_number" varchar,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "status" varchar not null default 'active',
  "joined_on" date,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "library_members_school_id_status_index" on "library_members"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "library_members_school_id_member_number_unique" on "library_members"(
  "school_id",
  "member_number"
);
CREATE TABLE IF NOT EXISTS "school_currencies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "symbol" varchar,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_currencies_school_id_code_unique" on "school_currencies"(
  "school_id",
  "code"
);
CREATE INDEX "school_currencies_school_id_index" on "school_currencies"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "students"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_number" varchar not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "full_name" varchar not null,
  "date_of_birth" date,
  "gender" varchar,
  "national_id" varchar,
  "phone" varchar,
  "email" varchar,
  "address" text,
  "suburb" varchar,
  "class" varchar,
  "school" varchar,
  "status" varchar not null default('active'),
  "previous_school" varchar,
  "balance" numeric not null default('0'),
  "currency" varchar not null default('USD'),
  "user_id" integer,
  "class_id" integer,
  "grade_level_id" integer,
  "guardian_first_name" varchar,
  "guardian_last_name" varchar,
  "guardian_phone" varchar,
  "guardian_email" varchar,
  "guardian_relationship" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "repetition_count" integer not null default('0'),
  "stream_id" integer,
  "house_id" integer,
  "status_reason" varchar,
  "status_changed_at" datetime,
  "exited_at" date,
  "student_category_id" integer,
  "photo_url" varchar,
  "roll_number" varchar,
  "blood_group" varchar,
  "passport_number" varchar,
  "social_links" text,
  foreign key("house_id") references houses("id") on delete set null on update no action,
  foreign key("stream_id") references streams("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("student_category_id") references "student_categories"("id") on delete set null
);
CREATE INDEX "students_class_id_index" on "students"("class_id");
CREATE INDEX "students_school_id_index" on "students"("school_id");
CREATE UNIQUE INDEX "students_school_id_student_number_unique" on "students"(
  "school_id",
  "student_number"
);
CREATE TABLE IF NOT EXISTS "leave_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "requested_by" integer,
  "type" varchar not null default('annual'),
  "start_date" date not null,
  "end_date" date not null,
  "days" integer not null default('1'),
  "reason" text,
  "status" varchar not null default('pending'),
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "review_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "leave_type_id" integer,
  foreign key("reviewed_by") references users("id") on delete set null on update no action,
  foreign key("requested_by") references users("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("leave_type_id") references "leave_types"("id") on delete set null
);
CREATE INDEX "leave_requests_school_id_status_index" on "leave_requests"(
  "school_id",
  "status"
);
CREATE INDEX "leave_requests_teacher_id_start_date_index" on "leave_requests"(
  "teacher_id",
  "start_date"
);
CREATE TABLE IF NOT EXISTS "teachers"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "employee_id" varchar not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "name" varchar not null,
  "email" varchar not null,
  "phone" varchar,
  "address" text,
  "subject" varchar,
  "department" varchar,
  "qualification" varchar,
  "joining_date" date,
  "status" varchar not null default('active'),
  "base_salary" numeric,
  "salary_currency" varchar not null default('USD'),
  "allowances" text,
  "deductions" text,
  "bank_name" varchar,
  "bank_account_number" varchar,
  "payment_method" varchar not null default('bank_transfer'),
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "employment_type" varchar not null default('full_time'),
  "period_rate" numeric,
  "designation_id" integer,
  "blood_group" varchar,
  "passport_number" varchar,
  "social_links" text,
  "emergency_contact_name" varchar,
  "emergency_contact_phone" varchar,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("designation_id") references "designations"("id") on delete set null
);
CREATE UNIQUE INDEX "teachers_school_id_email_unique" on "teachers"(
  "school_id",
  "email"
);
CREATE UNIQUE INDEX "teachers_school_id_employee_id_unique" on "teachers"(
  "school_id",
  "employee_id"
);
CREATE INDEX "teachers_school_id_index" on "teachers"("school_id");
CREATE TABLE IF NOT EXISTS "certificates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "certificate_type" varchar not null,
  "title" varchar not null,
  "verification_code" varchar not null,
  "content_html" text not null,
  "metadata" text,
  "issued_by" integer,
  "issued_at" datetime not null,
  "revoked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "certificate_template_id" integer,
  foreign key("issued_by") references users("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("certificate_template_id") references "certificate_templates"("id") on delete set null
);
CREATE INDEX "certificates_school_id_certificate_type_index" on "certificates"(
  "school_id",
  "certificate_type"
);
CREATE UNIQUE INDEX "certificates_verification_code_unique" on "certificates"(
  "verification_code"
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "invoice_number" varchar not null,
  "student_id" integer not null,
  "parent_id" integer,
  "fee_structure_id" integer,
  "description" varchar not null,
  "amount" numeric not null,
  "amount_paid" numeric not null default('0'),
  "balance" numeric not null,
  "currency" varchar not null default('USD'),
  "due_date" date not null,
  "status" varchar not null default('pending'),
  "created_at" datetime,
  "updated_at" datetime,
  "original_amount" numeric,
  "discount_amount" numeric not null default '0',
  "fee_discount_id" integer,
  "fee_group_id" integer,
  foreign key("fee_structure_id") references fee_structures("id") on delete set null on update no action,
  foreign key("parent_id") references users("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("fee_discount_id") references "fee_discounts"("id") on delete set null,
  foreign key("fee_group_id") references "fee_groups"("id") on delete set null
);
CREATE INDEX "invoices_parent_id_index" on "invoices"("parent_id");
CREATE INDEX "invoices_school_id_index" on "invoices"("school_id");
CREATE UNIQUE INDEX "invoices_school_id_invoice_number_unique" on "invoices"(
  "school_id",
  "invoice_number"
);
CREATE INDEX "invoices_school_status_due_idx" on "invoices"(
  "school_id",
  "status",
  "due_date"
);
CREATE INDEX "invoices_school_student_status_idx" on "invoices"(
  "school_id",
  "student_id",
  "status"
);
CREATE INDEX "invoices_student_id_index" on "invoices"("student_id");
CREATE TABLE IF NOT EXISTS "school_languages"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_languages_school_id_code_unique" on "school_languages"(
  "school_id",
  "code"
);
CREATE INDEX "school_languages_school_id_index" on "school_languages"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer,
  "payment_id" integer,
  "invoice_id" integer,
  "payroll_id" integer,
  "type" varchar not null,
  "category" varchar not null default('student'),
  "description" varchar not null,
  "reference" varchar,
  "debit" numeric not null default('0'),
  "credit" numeric not null default('0'),
  "balance" numeric not null,
  "currency" varchar not null default('USD'),
  "status" varchar not null default('completed'),
  "payment_method" varchar,
  "invoice_number" varchar,
  "created_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "income_head_id" integer,
  "expense_head_id" integer,
  foreign key("payroll_id") references payroll("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("payment_id") references payments("id") on delete set null on update no action,
  foreign key("invoice_id") references invoices("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("income_head_id") references "income_heads"("id") on delete set null,
  foreign key("expense_head_id") references "expense_heads"("id") on delete set null
);
CREATE INDEX "transactions_category_status_index" on "transactions"(
  "category",
  "status"
);
CREATE INDEX "transactions_school_created_idx" on "transactions"(
  "school_id",
  "created_at"
);
CREATE INDEX "transactions_school_id_index" on "transactions"("school_id");
CREATE INDEX "transactions_school_student_created_idx" on "transactions"(
  "school_id",
  "student_id",
  "created_at"
);
CREATE INDEX "transactions_student_id_index" on "transactions"("student_id");
CREATE TABLE IF NOT EXISTS "library_loans"(
  "id" integer primary key autoincrement not null,
  "book_id" integer not null,
  "student_id" integer,
  "borrowed_at" date not null,
  "due_at" date not null,
  "returned_at" date,
  "fine_amount" numeric not null default('0'),
  "status" varchar not null default('borrowed'),
  "created_at" datetime,
  "updated_at" datetime,
  "library_member_id" integer,
  foreign key("book_id") references library_books("id") on delete cascade on update no action,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("library_member_id") references "library_members"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_domains"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "domain" varchar not null,
  "is_primary" tinyint(1) not null default '0',
  "is_verified" tinyint(1) not null default '0',
  "status" varchar not null default 'active',
  "verified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "verification_token" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "school_domains_school_id_status_index" on "school_domains"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "school_domains_domain_unique" on "school_domains"(
  "domain"
);
CREATE TABLE IF NOT EXISTS "school_backups"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "path" varchar not null,
  "status" varchar not null default 'completed',
  "table_count" integer not null default '0',
  "row_count" integer not null default '0',
  "file_size" integer not null default '0',
  "created_by" integer,
  "restored_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "school_backups_school_id_created_at_index" on "school_backups"(
  "school_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "help_desk_tickets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "ticket_number" varchar not null,
  "subject" varchar not null,
  "description" text,
  "category" varchar not null default 'general',
  "priority" varchar not null default 'normal',
  "status" varchar not null default 'open',
  "requester_name" varchar not null,
  "requester_email" varchar,
  "requester_phone" varchar,
  "student_id" integer,
  "assigned_to" integer,
  "created_by" integer,
  "resolved_at" datetime,
  "closed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("assigned_to") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "help_desk_tickets_school_id_ticket_number_unique" on "help_desk_tickets"(
  "school_id",
  "ticket_number"
);
CREATE INDEX "help_desk_tickets_school_id_status_index" on "help_desk_tickets"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "job_postings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "department" varchar,
  "employment_type" varchar not null default 'full_time',
  "location" varchar,
  "description" text,
  "requirements" text,
  "openings" integer not null default '1',
  "status" varchar not null default 'draft',
  "published_at" datetime,
  "closes_at" datetime,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "job_postings_school_id_status_index" on "job_postings"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "job_applications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "job_posting_id" integer not null,
  "applicant_name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "resume_url" varchar,
  "cover_letter" text,
  "status" varchar not null default 'submitted',
  "notes" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("job_posting_id") references "job_postings"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE INDEX "job_applications_school_id_status_index" on "job_applications"(
  "school_id",
  "status"
);
CREATE INDEX "job_applications_job_posting_id_status_index" on "job_applications"(
  "job_posting_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "budgets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "fiscal_year" varchar not null,
  "department" varchar,
  "allocated_amount" numeric not null,
  "spent_amount" numeric not null default('0'),
  "currency" varchar not null default('USD'),
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'draft',
  "notes" text,
  "approved_by" integer,
  "approved_at" datetime,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_user_memberships"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "role" varchar,
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_user_memberships_school_id_user_id_unique" on "school_user_memberships"(
  "school_id",
  "user_id"
);
CREATE INDEX "school_user_memberships_user_id_is_default_index" on "school_user_memberships"(
  "user_id",
  "is_default"
);
CREATE TABLE IF NOT EXISTS "webhook_subscriptions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "event_type" varchar not null,
  "target_url" varchar not null,
  "secret_hash" varchar,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "secret" text,
  foreign key("school_id") references schools("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "webhook_deliveries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subscription_id" integer not null,
  "event_type" varchar not null,
  "payload" text not null,
  "status" varchar not null default 'pending',
  "attempts" integer not null default '0',
  "response_status" integer,
  "response_body" text,
  "last_error" text,
  "next_retry_at" datetime,
  "delivered_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subscription_id") references "webhook_subscriptions"("id") on delete cascade
);
CREATE INDEX "webhook_deliveries_school_id_status_index" on "webhook_deliveries"(
  "school_id",
  "status"
);
CREATE INDEX "webhook_deliveries_status_next_retry_at_index" on "webhook_deliveries"(
  "status",
  "next_retry_at"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2024_01_01_000000_create_schools_table',1);
INSERT INTO migrations VALUES(5,'2024_01_20_000000_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(6,'2024_01_20_100000_create_students_table',1);
INSERT INTO migrations VALUES(7,'2024_01_20_100001_create_teachers_table',1);
INSERT INTO migrations VALUES(8,'2024_01_20_100002_create_classes_table',1);
INSERT INTO migrations VALUES(9,'2024_01_20_100003_create_payments_table',1);
INSERT INTO migrations VALUES(10,'2024_01_20_100004_create_invoices_table',1);
INSERT INTO migrations VALUES(11,'2024_01_20_100005_create_grades_table',1);
INSERT INTO migrations VALUES(12,'2024_01_20_100006_create_attendance_table',1);
INSERT INTO migrations VALUES(13,'2024_01_20_100007_create_timetable_table',1);
INSERT INTO migrations VALUES(14,'2024_01_20_100008_create_fee_structures_table',1);
INSERT INTO migrations VALUES(15,'2024_01_20_100009_create_transactions_table',1);
INSERT INTO migrations VALUES(16,'2024_01_23_100000_create_enrollment_applications_table',1);
INSERT INTO migrations VALUES(17,'2024_01_23_100001_create_announcements_table',1);
INSERT INTO migrations VALUES(18,'2024_01_23_100002_create_assignments_table',1);
INSERT INTO migrations VALUES(19,'2024_01_23_100003_create_parent_student_table',1);
INSERT INTO migrations VALUES(20,'2026_01_22_123134_create_settings_table',1);
INSERT INTO migrations VALUES(21,'2026_01_23_114727_create_enrollments_table',1);
INSERT INTO migrations VALUES(22,'2026_01_23_195455_create_departments_table',1);
INSERT INTO migrations VALUES(23,'2026_01_23_195542_create_fee_categories_table',1);
INSERT INTO migrations VALUES(24,'2026_01_24_000001_create_schools_table',1);
INSERT INTO migrations VALUES(25,'2026_01_24_000002_create_roles_table',1);
INSERT INTO migrations VALUES(26,'2026_01_24_000003_create_subjects_table',1);
INSERT INTO migrations VALUES(27,'2026_01_24_000004_create_payroll_table',1);
INSERT INTO migrations VALUES(28,'2026_01_24_000007_add_foreign_keys_to_users',1);
INSERT INTO migrations VALUES(29,'2026_01_25_000001_create_report_templates_table',1);
INSERT INTO migrations VALUES(30,'2026_01_27_000002_create_grade_levels_table',1);
INSERT INTO migrations VALUES(31,'2026_01_27_000003_create_rooms_table',1);
INSERT INTO migrations VALUES(32,'2026_01_27_000004_create_grading_scales_table',1);
INSERT INTO migrations VALUES(33,'2026_01_27_000005_create_teacher_assignments_table',1);
INSERT INTO migrations VALUES(34,'2026_01_27_000006_create_guardians_table',1);
INSERT INTO migrations VALUES(35,'2026_01_27_000007_create_guardian_student_table',1);
INSERT INTO migrations VALUES(36,'2026_01_27_000013_create_notification_queue_table',1);
INSERT INTO migrations VALUES(37,'2026_01_27_000014_create_terms_table',1);
INSERT INTO migrations VALUES(38,'2026_01_27_000015_create_exams_table',1);
INSERT INTO migrations VALUES(39,'2026_01_27_000016_create_exam_results_table',1);
INSERT INTO migrations VALUES(40,'2026_01_27_000017_create_tests_table',1);
INSERT INTO migrations VALUES(41,'2026_01_27_000018_create_test_results_table',1);
INSERT INTO migrations VALUES(42,'2026_01_27_000019_add_missing_foreign_key_constraints',1);
INSERT INTO migrations VALUES(43,'2026_01_28_000001_add_permission_ids_to_roles_table',1);
INSERT INTO migrations VALUES(44,'2026_01_28_000002_add_status_to_users_table',1);
INSERT INTO migrations VALUES(45,'2026_01_28_000003_add_assessment_links_to_grades_table',1);
INSERT INTO migrations VALUES(46,'2026_01_28_000004_add_amount_paid_to_payroll_table',1);
INSERT INTO migrations VALUES(47,'2026_06_20_000001_create_school_settings_table',1);
INSERT INTO migrations VALUES(48,'2026_06_20_000001_fix_attendance_unique_index',1);
INSERT INTO migrations VALUES(49,'2026_06_20_000002_create_custom_fields_table',1);
INSERT INTO migrations VALUES(50,'2026_06_20_000003_create_custom_field_values_table',1);
INSERT INTO migrations VALUES(51,'2026_06_20_000004_create_terminology_mappings_table',1);
INSERT INTO migrations VALUES(52,'2026_06_20_202240_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(53,'2026_06_20_202241_create_passkeys_table',1);
INSERT INTO migrations VALUES(54,'2026_06_20_203743_create_agent_conversations_table',1);
INSERT INTO migrations VALUES(55,'2026_06_21_000001_add_deferred_foreign_key_constraints',1);
INSERT INTO migrations VALUES(56,'2026_06_21_000002_create_leave_requests_table',1);
INSERT INTO migrations VALUES(57,'2026_06_21_000003_create_student_documents_table',1);
INSERT INTO migrations VALUES(58,'2026_06_22_000001_add_student_lifecycle_columns',1);
INSERT INTO migrations VALUES(59,'2026_06_22_000002_create_inventory_tables',1);
INSERT INTO migrations VALUES(60,'2026_06_22_000003_create_holiday_program_tables',1);
INSERT INTO migrations VALUES(61,'2026_06_22_000004_create_parent_portal_tables',1);
INSERT INTO migrations VALUES(62,'2026_06_22_000005_create_audit_tables',1);
INSERT INTO migrations VALUES(63,'2026_06_22_000006_create_school_erp_modules',1);
INSERT INTO migrations VALUES(64,'2026_06_22_000007_create_advanced_platform_features',1);
INSERT INTO migrations VALUES(65,'2026_06_22_000008_create_enterprise_erp_modules',1);
INSERT INTO migrations VALUES(66,'2026_06_22_000009_create_school_license_keys',1);
INSERT INTO migrations VALUES(67,'2026_06_22_120000_add_finance_performance_indexes',1);
INSERT INTO migrations VALUES(68,'2026_06_23_000001_add_avatar_url_to_users_table',1);
INSERT INTO migrations VALUES(69,'2026_07_19_000001_add_disbursement_fields_to_purchase_requisitions',1);
INSERT INTO migrations VALUES(70,'2026_07_19_000001_add_platform_terms_acceptance_to_users_table',1);
INSERT INTO migrations VALUES(71,'2026_07_19_000002_create_school_trip_tables',1);
INSERT INTO migrations VALUES(72,'2026_07_19_014200_add_student_placement_lifecycle_tables',1);
INSERT INTO migrations VALUES(73,'2026_07_19_114900_add_fee_category_id_to_fee_structures_table',1);
INSERT INTO migrations VALUES(74,'2026_07_19_120000_add_permission_ids_to_users_table',1);
INSERT INTO migrations VALUES(75,'2026_07_20_112600_add_profile_fields_to_schools_table',1);
INSERT INTO migrations VALUES(76,'2026_07_23_000001_create_teacher_portal_tables',1);
INSERT INTO migrations VALUES(77,'2026_07_26_200000_add_edudash_parity_modules',1);
INSERT INTO migrations VALUES(78,'2026_07_26_203000_add_discount_fields_to_invoices_table',1);
INSERT INTO migrations VALUES(79,'2026_07_26_204000_create_school_languages_table',1);
INSERT INTO migrations VALUES(80,'2026_07_26_205000_add_accounting_heads_to_transactions_table',1);
INSERT INTO migrations VALUES(81,'2026_07_26_210000_add_library_member_id_to_library_loans_table',1);
INSERT INTO migrations VALUES(82,'2026_07_28_120000_create_school_domains_table',1);
INSERT INTO migrations VALUES(83,'2026_07_28_130000_add_verification_token_to_school_domains',1);
INSERT INTO migrations VALUES(84,'2026_07_28_130100_create_school_backups_table',1);
INSERT INTO migrations VALUES(85,'2026_07_28_140000_create_help_desk_tickets_table',1);
INSERT INTO migrations VALUES(86,'2026_07_28_140100_add_photo_url_to_students_table',1);
INSERT INTO migrations VALUES(87,'2026_07_28_150000_add_revenue_fields_to_license_keys',1);
INSERT INTO migrations VALUES(88,'2026_07_28_150000_create_recruitment_tables',1);
INSERT INTO migrations VALUES(89,'2026_07_28_160000_add_workflow_fields_to_budgets',1);
INSERT INTO migrations VALUES(90,'2026_07_28_160000_create_school_user_memberships_table',1);
INSERT INTO migrations VALUES(91,'2026_07_28_170000_create_webhook_deliveries_and_secrets',1);
INSERT INTO migrations VALUES(92,'2026_10_06_095715_add_preskool_ui_parity_fields_to_tables',2);
