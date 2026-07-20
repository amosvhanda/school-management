/**
 * Verifies student wizard validation + payload mapping + API save.
 * Run: node scripts/verify-student-wizard.mjs
 */
import { z } from 'zod'

const API = process.env.API_URL ?? 'http://127.0.0.1:8000/api/v1'

const studentFormSchema = z
  .object({
    firstName: z.string().trim().min(1, 'First name is required').max(255),
    surname: z.string().trim().min(1, 'Surname is required').max(255),
    class_id: z.string().min(1, 'Class is required'),
    grade_level_id: z.string().optional().or(z.literal('')),
    dateOfBirth: z
      .string()
      .min(1, 'Date of birth is required')
      .regex(/^\d{4}-\d{2}-\d{2}$/, 'Enter a valid date'),
    gender: z.enum(['male', 'female', 'other'], {
      required_error: 'Please select gender',
    }),
    phone: z.string().optional().or(z.literal('')),
    email: z.string().optional().or(z.literal('')),
    suburb: z.string().optional().or(z.literal('')),
    address: z.string().optional().or(z.literal('')),
    guardianMode: z.enum(['existing', 'new', 'none']).default('new'),
    guardian_id: z.string().optional().or(z.literal('')),
    guardianFirstName: z.string().optional().or(z.literal('')),
    guardianSurname: z.string().optional().or(z.literal('')),
    guardianPhone: z.string().optional().or(z.literal('')),
    guardianEmail: z.string().optional().or(z.literal('')),
    guardianRelationship: z.string().optional().or(z.literal('')),
  })
  .superRefine((data, ctx) => {
    if (data.guardianMode === 'none') return
    if (data.guardianMode === 'existing') {
      if (!data.guardian_id) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          message: 'Select a guardian from the register',
          path: ['guardian_id'],
        })
      }
      return
    }
    if (!data.guardianFirstName?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Guardian first name is required',
        path: ['guardianFirstName'],
      })
    }
    if (!data.guardianPhone?.trim()) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Guardian mobile is required',
        path: ['guardianPhone'],
      })
    }
  })

async function setupDom() {
  const { JSDOM } = await import('jsdom')
  const dom = new JSDOM('<!DOCTYPE html><html><body></body></html>')
  for (const key of ['document', 'window', 'HTMLElement', 'SVGElement', 'Element', 'Node', 'Text']) {
    globalThis[key] = dom.window[key]
  }
}

async function login(email, password) {
  const res = await fetch(`${API}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ email, password }),
  })
  const json = await res.json()
  if (!res.ok) throw new Error(`Login failed: ${JSON.stringify(json)}`)
  return json.data.token
}

/** Simulate staged wizard: mount step-1 fields, type values, unmount, then validate. */
async function testWizardUnmount(keepValuesOnUnmount) {
  const { createApp, defineComponent, h, nextTick, ref } = await import('vue')
  const { Field, useForm } = await import('vee-validate')
  const { toTypedSchema } = await import('@vee-validate/zod')

  const el = document.createElement('div')
  document.body.appendChild(el)

  return new Promise((resolve) => {
    const step = ref(0)

    const Harness = defineComponent({
      setup() {
        const form = useForm({
          validationSchema: toTypedSchema(studentFormSchema),
          initialValues: {
            firstName: '',
            surname: '',
            class_id: '',
            dateOfBirth: '',
            gender: undefined,
            guardianMode: 'none',
          },
          keepValuesOnUnmount,
        })

        function fieldInput(name) {
          return defineComponent({
            props: { show: Boolean },
            setup(props) {
              return () =>
                props.show
                  ? h(Field, { name }, {
                      default: ({ field }) =>
                        h('input', {
                          'data-field': name,
                          value: field.value ?? '',
                          onInput: (e) => field.onChange(e.target.value),
                        }),
                    })
                  : null
            },
          })
        }

        const run = async () => {
          const fill = (name, value) => {
            const input = el.querySelector(`input[data-field="${name}"]`)
            if (!input) throw new Error(`Missing input for ${name}`)
            input.value = value
            input.dispatchEvent(new window.Event('input', { bubbles: true }))
          }
          fill('firstName', 'Wizard')
          fill('surname', 'Test')
          fill('dateOfBirth', '2012-06-15')
          fill('gender', 'male')
          await nextTick()
          step.value = 1
          await nextTick()
          fill('class_id', '2')
          await nextTick()
          const result = await form.validate()
          resolve({
            keepValuesOnUnmount,
            valid: result.valid,
            firstName: form.values.firstName,
            gender: form.values.gender,
            errors: result.errors,
          })
        }

        return () =>
          h('div', [
            h(fieldInput('firstName'), { show: step.value === 0 }),
            h(fieldInput('surname'), { show: step.value === 0 }),
            h(fieldInput('dateOfBirth'), { show: step.value === 0 }),
            h(fieldInput('gender'), { show: step.value === 0 }),
            h(fieldInput('class_id'), { show: step.value === 1 }),
            h('button', { onClick: run }, 'run'),
          ])
      },
    })

    createApp(Harness).mount(el)
    el.querySelector('button').click()
  })
}

async function main() {
  await setupDom()

  console.log('1. Schema: empty step-1 values should fail (simulates wizard bug)')
  const empty = studentFormSchema.safeParse({
    class_id: '2',
    guardianMode: 'none',
  })
  if (empty.success) throw new Error('Expected validation failure for empty step 1')
  console.log('   OK — validation fails without step-1 fields')

  console.log('2. Schema: complete wizard values should pass')
  const complete = studentFormSchema.safeParse({
    firstName: 'Wizard',
    surname: 'Test',
    class_id: '2',
    dateOfBirth: '2012-06-15',
    gender: 'male',
    guardianMode: 'none',
  })
  if (!complete.success) throw new Error(JSON.stringify(complete.error.flatten()))
  console.log('   OK')

  console.log('3. vee-validate: keepValuesOnUnmount=false drops values on step change')
  const without = await testWizardUnmount(false)
  if (without.valid || without.firstName === 'Wizard') {
    throw new Error(`Expected failure / lost values, got: ${JSON.stringify(without)}`)
  }
  console.log('   OK — bug reproduced (valid=%s, firstName=%s)', without.valid, without.firstName)

  console.log('4. vee-validate: keepValuesOnUnmount=true retains values (our fix)')
  const withKeep = await testWizardUnmount(true)
  if (!withKeep.valid || withKeep.firstName !== 'Wizard' || withKeep.gender !== 'male') {
    throw new Error(`Fix failed: ${JSON.stringify(withKeep)}`)
  }
  console.log('   OK — step-1 values survive unmount and full validation passes')

  console.log('5. API create with wizard-equivalent payload')
  const token = await login('teacher@school.co.zw', 'teacher123')
  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }
  const classesRes = await fetch(`${API}/classes?all=1&limit=1`, { headers })
  const classesJson = await classesRes.json()
  const classRow = (classesJson.data ?? classesJson)[0]
  const createRes = await fetch(`${API}/students`, {
    method: 'POST',
    headers,
    body: JSON.stringify({
      firstName: 'Wizard',
      surname: 'Verified',
      class_id: classRow.id,
      dateOfBirth: '2012-06-15',
      gender: 'male',
    }),
  })
  const createJson = await createRes.json()
  if (!createRes.ok) {
    console.error('CREATE FAILED', createJson)
    process.exit(1)
  }
  console.log('   OK — created student id', createJson.data?.id)

  console.log('\nAll student wizard save checks passed.')
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
