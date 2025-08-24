<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

/**
 * EXPECTED PROPS from controller:
 * return Inertia::render('Transactions/Create', [
 *   'accounts'   => Account::select('id','name')->orderBy('name')->get(),
 *   'categories' => TransactionCategory::select('id','name')->orderBy('name')->get(),
 * ]);
 */
type Option = { id: number | string; name: string }

const props = defineProps<{
  accounts: Option[]
  categories: Option[]
}>()

const breadcrumbs = [
  { title: 'Transactions', href: route('transactionos.index') },
  { title: 'Create' },
]

/** ---- Form model (matches your migration/columns) ---- */
const form = useForm({
  name: '',
  category_id: null as number | string | null,

  from_account_id: null as number | string | null,
  send_actual_amount: '' as number | string,
  send_total_amount: 0 as number,

  to_account_id: null as number | string | null,
  receive_actual_amount: '' as number | string,
  receive_total_amount: 0 as number,

  description: '' as string | null,
  attachments: [] as File[], // multiple files
})

/** ---- Dynamic fees (NOT saved to DB; used to compute totals) ---- */
type FeeRow = { name: string; amount: number | string }

const sourceFees = reactive<FeeRow[]>([{ name: '', amount: '' }])
const destFees   = reactive<FeeRow[]>([{ name: '', amount: '' }])

function addSourceFee()  { sourceFees.push({ name: '', amount: '' }) }
function addDestFee()    { destFees.push({ name: '', amount: '' }) }
function removeSourceFee(i: number) { if (sourceFees.length > 1) sourceFees.splice(i, 1) }
function removeDestFee(i: number)   { if (destFees.length > 1) destFees.splice(i, 1) }

const n = (v: unknown) => {
  const x = typeof v === 'string' ? v.replace(/,/g, '') : v
  const num = Number(x)
  return isFinite(num) ? num : 0
}

const sourceFeesTotal = computed(() => sourceFees.reduce((sum, f) => sum + n(f.amount), 0))
const destFeesTotal   = computed(() => destFees.reduce((sum, f) => sum + n(f.amount), 0))

/** Auto-compute totals from actual + fees */
watch([() => form.send_actual_amount, sourceFeesTotal], () => {
  form.send_total_amount = n(form.send_actual_amount) + sourceFeesTotal.value
}, { immediate: true })

watch([() => form.receive_actual_amount, destFeesTotal], () => {
  form.receive_total_amount = n(form.receive_actual_amount) + destFeesTotal.value
}, { immediate: true })

/** Attachments input -> form.attachments */
function onFilesChanged(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files) return
  form.attachments = Array.from(input.files)
}
function removeFile(i: number) {
  form.attachments.splice(i, 1)
}

/** Submit -> POST as multipart/form-data */
function submit() {
  form.transform((data) => {
    const fd = new FormData()

    fd.append('name', data.name ?? '')
    fd.append('category_id', data.category_id ? String(data.category_id) : '')

    fd.append('from_account_id', data.from_account_id ? String(data.from_account_id) : '')
    fd.append('send_actual_amount', String(n(data.send_actual_amount)))
    fd.append('send_total_amount',  String(n(data.send_total_amount)))

    fd.append('to_account_id', data.to_account_id ? String(data.to_account_id) : '')
    fd.append('receive_actual_amount', String(n(data.receive_actual_amount)))
    fd.append('receive_total_amount',  String(n(data.receive_total_amount)))

    fd.append('description', data.description ?? '')

    // files
    for (const f of data.attachments as File[]) {
      fd.append('attachments[]', f)
    }

    // NOTE: fees are not posted (no columns). If you later add columns, send them (e.g. JSON).
    return fd
  }).post(route('transactionos.store'), {
    preserveScroll: true,
    onFinish: () => form.reset('attachments'),
  })
}

/** ---- Right-side: tiny calculator ---- */
const calc = reactive({
  display: '' as string,
  result: '' as string,
})
function append(val: string) {
  calc.display += val
}
function clearAll() {
  calc.display = ''
  calc.result = ''
}
function backspace() {
  calc.display = calc.display.slice(0, -1)
}
function evaluate() {
  try {
    // very simple evaluator for basic arithmetic
    // eslint-disable-next-line no-new-func
    const out = Function(`"use strict"; return (${calc.display});`)()
    calc.result = String(out)
  } catch {
    calc.result = 'Error'
  }
}
</script>

<template>
  <Head title="Create Transaction" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- LEFT: FORM -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">New Transaction</h1>
            <div class="flex gap-2">
              <Link
                :href="route('transactionos.index')"
                as="button"
                class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                Cancel
              </Link>
              <button
                type="button"
                class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-white dark:text-gray-900"
                :disabled="form.processing"
                @click="submit"
              >
                Save
              </button>
            </div>
          </div>

          <form @submit.prevent="submit" class="grid gap-6">
            <!-- Top: Name + Category -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Transaction Name <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-lg border px-3 py-2"
                  :class="form.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                  placeholder="e.g. Transfer to Savings / Vendor Payment"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium">Category <span class="text-red-500">*</span></label>
                <select
                  v-model="form.category_id"
                  class="w-full rounded-lg border px-3 py-2"
                  :class="form.errors.category_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                >
                  <option :value="null">— Select —</option>
                  <option v-for="c in props.categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
              </div>
            </div>

            <!-- SOURCE (Sender) -->
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
              <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Source (Sender)</h2>

              <!-- Account + Amount (actual) -->
              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium">Source Account <span class="text-red-500">*</span></label>
                  <select
                    v-model="form.from_account_id"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.from_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                  >
                    <option :value="null">— Select Account —</option>
                    <option v-for="a in props.accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                  <p v-if="form.errors.from_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.from_account_id }}</p>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium">Send Amount (Actual) <span class="text-red-500">*</span></label>
                  <input
                    v-model="form.send_actual_amount"
                    type="number"
                    step="0.01"
                    inputmode="decimal"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.send_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                    placeholder="0.00"
                  />
                  <p v-if="form.errors.send_actual_amount" class="mt-1 text-xs text-red-600">{{ form.errors.send_actual_amount }}</p>
                </div>
              </div>

              <!-- Fees (dynamic) -->
              <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                  <h3 class="text-sm font-medium">Fees (Sender)</h3>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addSourceFee">
                    + Add Fee
                  </button>
                </div>

                <div class="grid gap-2">
                  <div
                    v-for="(f, i) in sourceFees"
                    :key="i"
                    class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]"
                  >
                    <div>
                      <label class="mb-1 block text-xs font-medium">Name</label>
                      <input v-model="f.name" type="text" class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700" />
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium">Amount</label>
                      <input v-model="f.amount" type="number" step="0.01" inputmode="decimal" class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700" />
                    </div>
                    <div class="flex justify-end">
                      <button
                        v-if="i > 0"
                        type="button"
                        class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="removeSourceFee(i)"
                      >
                        ✕
                      </button>
                      <button
                        v-else
                        type="button"
                        class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="addSourceFee"
                      >
                        + Add
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Computed total -->
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="text-sm text-gray-600 dark:text-gray-300">
                    Fees Total: <span class="font-semibold">{{ sourceFeesTotal.toFixed(2) }}</span>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Send Total (computed)</label>
                    <input
                      :value="form.send_total_amount.toFixed(2)"
                      type="text"
                      readonly
                      class="w-full cursor-not-allowed rounded-lg border px-3 py-2 bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- DESTINATION (Receiver) -->
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
              <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Destination (Receiver)</h2>

              <!-- Account + Amount (actual) -->
              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium">Destination Account <span class="text-red-500">*</span></label>
                  <select
                    v-model="form.to_account_id"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.to_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                  >
                    <option :value="null">— Select Account —</option>
                    <option v-for="a in props.accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                  <p v-if="form.errors.to_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.to_account_id }}</p>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium">Receive Amount (Actual) <span class="text-red-500">*</span></label>
                  <input
                    v-model="form.receive_actual_amount"
                    type="number"
                    step="0.01"
                    inputmode="decimal"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.receive_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                    placeholder="0.00"
                  />
                  <p v-if="form.errors.receive_actual_amount" class="mt-1 text-xs text-red-600">{{ form.errors.receive_actual_amount }}</p>
                </div>
              </div>

              <!-- Fees (dynamic) -->
              <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                  <h3 class="text-sm font-medium">Fees (Receiver)</h3>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addDestFee">
                    + Add Fee
                  </button>
                </div>

                <div class="grid gap-2">
                  <div
                    v-for="(f, i) in destFees"
                    :key="i"
                    class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]"
                  >
                    <div>
                      <label class="mb-1 block text-xs font-medium">Name</label>
                      <input v-model="f.name" type="text" class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700" />
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium">Amount</label>
                      <input v-model="f.amount" type="number" step="0.01" inputmode="decimal" class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700" />
                    </div>
                    <div class="flex justify-end">
                      <button
                        v-if="i > 0"
                        type="button"
                        class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="removeDestFee(i)"
                      >
                        ✕
                      </button>
                      <button
                        v-else
                        type="button"
                        class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        @click="addDestFee"
                      >
                        + Add
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Computed total -->
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="text-sm text-gray-600 dark:text-gray-300">
                    Fees Total: <span class="font-semibold">{{ destFeesTotal.toFixed(2) }}</span>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Receive Total (computed)</label>
                    <input
                      :value="form.receive_total_amount.toFixed(2)"
                      type="text"
                      readonly
                      class="w-full cursor-not-allowed rounded-lg border px-3 py-2 bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="mb-1 block text-sm font-medium">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
                placeholder="Optional notes..."
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <!-- Attachments -->
            <div>
              <label class="mb-1 block text-sm font-medium">Attachments</label>
              <input
                type="file"
                multiple
                @change="onFilesChanged"
                class="block w-full cursor-pointer rounded-lg border border-dashed border-gray-300 p-3 text-sm dark:border-gray-700"
              />
              <ul v-if="form.attachments.length" class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                <li
                  v-for="(f, idx) in form.attachments"
                  :key="`${f.name}-${idx}`"
                  class="flex items-center justify-between px-3 py-2 text-sm"
                >
                  <span class="truncate">{{ f.name }}</span>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="removeFile(idx)">
                    Remove
                  </button>
                </li>
              </ul>
              <p v-if="form.errors['attachments']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments'] }}</p>
              <p v-if="form.errors['attachments.*']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.*'] }}</p>
            </div>
          </form>
        </section>

        <!-- RIGHT: CALCULATOR -->
        <aside class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Calculator</h2>

          <div class="space-y-3">
            <input
              :value="calc.display"
              readonly
              class="w-full rounded-lg border px-3 py-2 text-right text-lg bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800"
            />
            <input
              :value="calc.result"
              readonly
              class="w-full rounded-lg border px-3 py-2 text-right text-base bg-gray-50 dark:bg-gray-900/40 border-gray-200 dark:border-gray-800"
              placeholder="Result"
            />

            <div class="grid grid-cols-4 gap-2">
              <button class="rounded-lg border px-3 py-2" @click="append('7')">7</button>
              <button class="rounded-lg border px-3 py-2" @click="append('8')">8</button>
              <button class="rounded-lg border px-3 py-2" @click="append('9')">9</button>
              <button class="rounded-lg border px-3 py-2" @click="append('/')">÷</button>

              <button class="rounded-lg border px-3 py-2" @click="append('4')">4</button>
              <button class="rounded-lg border px-3 py-2" @click="append('5')">5</button>
              <button class="rounded-lg border px-3 py-2" @click="append('6')">6</button>
              <button class="rounded-lg border px-3 py-2" @click="append('*')">×</button>

              <button class="rounded-lg border px-3 py-2" @click="append('1')">1</button>
              <button class="rounded-lg border px-3 py-2" @click="append('2')">2</button>
              <button class="rounded-lg border px-3 py-2" @click="append('3')">3</button>
              <button class="rounded-lg border px-3 py-2" @click="append('-')">−</button>

              <button class="rounded-lg border px-3 py-2" @click="append('0')">0</button>
              <button class="rounded-lg border px-3 py-2" @click="append('.')">.</button>
              <button class="rounded-lg border px-3 py-2" @click="backspace()">⌫</button>
              <button class="rounded-lg border px-3 py-2" @click="append('+')">+</button>

              <button class="col-span-2 rounded-lg border px-3 py-2" @click="clearAll()">C</button>
              <button class="col-span-2 rounded-lg border px-3 py-2" @click="evaluate()">=</button>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
              Tip: The “Send/Receive Total” fields on the left are calculated automatically as <em>Actual + Fees</em>.
            </p>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
