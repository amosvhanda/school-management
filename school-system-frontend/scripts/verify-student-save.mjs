/**
 * Verifies student create/update via API (teacher account).
 * Run: node scripts/verify-student-save.mjs
 * Requires Laravel API at http://127.0.0.1:8000
 */
const API = process.env.API_URL ?? 'http://127.0.0.1:8000/api/v1'

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

async function main() {
  const token = await login('teacher@school.co.zw', 'teacher123')
  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    Accept: 'application/json',
  }

  const classesRes = await fetch(`${API}/classes?all=1&limit=5`, { headers })
  const classesJson = await classesRes.json()
  const classes = classesJson.data ?? classesJson
  const classRow = Array.isArray(classes) ? classes[0] : null
  if (!classRow?.id) throw new Error('No classes found for school')

  const createPayload = {
    firstName: 'Verify',
    surname: 'StudentSave',
    class_id: classRow.id,
    dateOfBirth: '2012-03-10',
    gender: 'male',
  }

  const createRes = await fetch(`${API}/students`, {
    method: 'POST',
    headers,
    body: JSON.stringify(createPayload),
  })
  const createJson = await createRes.json()
  if (!createRes.ok) {
    console.error('CREATE FAILED', createRes.status, createJson)
    process.exit(1)
  }

  const id = createJson.data?.id
  console.log('CREATE OK', { id, name: createJson.data?.full_name, class_id: createJson.data?.class_id })

  const updateRes = await fetch(`${API}/students/${id}`, {
    method: 'PUT',
    headers,
    body: JSON.stringify({ firstName: 'VerifyUpdated', suburb: 'Mufakose' }),
  })
  const updateJson = await updateRes.json()
  if (!updateRes.ok) {
    console.error('UPDATE FAILED', updateRes.status, updateJson)
    process.exit(1)
  }

  console.log('UPDATE OK', { id, first_name: updateJson.data?.first_name, suburb: updateJson.data?.suburb })
  console.log('Student API save flow verified.')
}

main().catch((err) => {
  console.error(err)
  process.exit(1)
})
