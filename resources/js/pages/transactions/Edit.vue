<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

type AccountWithBal = {
  id: number | string;
  name: string;
  currency: string | null;
  current_balance: string | number | null;
};
type Category = { id: number | string; name: string };
type TxType = 'income' | 'expense' | 'asset';
type FeeRow = { name: string; amount: number | string };

type TxPayload = {
  id: number | string;
  type: TxType; // normalized by controller
  name: string;
  category_id: number | string | null;
  from_account_id: number | string | null;
  to_account_id: number | string | null;
  description: string | null;
  attachments: string[]; // existing files
  send_total_amount: number;
  send_actual_amount: number | string;
  receive_total_amount: number;
  receive_actual_amount: number | string;
  created_at?: string | null;
  updated_at?: string | null;
};

const props = defineProps<{
  transaction: TxPayload;
  source_fees: FeeRow[];
  dest_fees: FeeRow[];
  categories: Category[];
  accounts: AccountWithBal[];         // Asset accounts
  exp_inc_accounts: AccountWithBal[]; // Expense/Income accounts
}>();

const breadcrumbs = [
  { title: 'Transactions', href: route('transactions.index') },
  { title: 'Edit' },
];

const txType = ref<TxType>(props.transaction.type);

const form = useForm({
  type: txType.value as TxType,

  name: props.transaction.name ?? '',
  category_id: props.transaction.category_id ?? null,

  from_account_id: props.transaction.from_account_id ?? null,
  send_actual_amount: String(props.transaction.send_actual_amount ?? ''),
  send_total_amount: Number(props.transaction.send_total_amount ?? 0),

  to_account_id: props.transaction.to_account_id ?? null,
  receive_actual_amount: String(props.transaction.receive_actual_amount ?? ''),
  receive_total_amount: Number(props.transaction.receive_total_amount ?? 0),

  description: props.transaction.description ?? '',
  attachments: [] as File[], // new uploads only
});

watch(txType, () => { form.type = txType.value; });

const n = (v: unknown) => {
  const x = typeof v === 'string' ? v.replace(/,/g, '') : v;
  const num = Number(x);
  return isFinite(num) ? num : 0;
};
const isDepleted = (acc?: AccountWithBal | null) => !acc || n(acc.current_balance) <= 0;

const sourceFees = reactive<FeeRow[]>(
  (props.source_fees?.length ? props.source_fees : [{ name: '', amount: '' }]).map(f => ({ ...f }))
);
const destFees = reactive<FeeRow[]>(
  (props.dest_fees?.length ? props.dest_fees : [{ name: '', amount: '' }]).map(f => ({ ...f }))
);
function addSourceFee() { sourceFees.push({ name: '', amount: '' }); }
function addDestFee()   { destFees.push({ name: '', amount: '' }); }
function removeSourceFee(i: number) { if (sourceFees.length > 1) sourceFees.splice(i, 1); }
function removeDestFee(i: number)   { if (destFees.length > 1) destFees.splice(i, 1); }

const sourceFeesTotal = computed(() => sourceFees.reduce((s, f) => s + n(f.amount), 0));
const destFeesTotal   = computed(() => destFees.reduce((s, f) => s + n(f.amount), 0));

watch([() => form.send_actual_amount, sourceFeesTotal], () => {
  form.send_total_amount = n(form.send_actual_amount) + sourceFeesTotal.value;
}, { immediate: true });
watch([() => form.receive_actual_amount, destFeesTotal], () => {
  form.receive_total_amount = n(form.receive_actual_amount) + destFeesTotal.value;
}, { immediate: true });

const sourceOptions = computed<AccountWithBal[]>(() => {
  if (txType.value === 'income') return props.exp_inc_accounts; // E/I ➜ Asset
  return props.accounts;                                        // Expense/Asset: Asset
});
const destOptions = computed<AccountWithBal[]>(() => {
  if (txType.value === 'expense') return props.exp_inc_accounts; // Asset ➜ E/I
  return props.accounts;                                         // Income/Asset: Asset
});

const selectedSource = computed(() =>
  sourceOptions.value.find(a => String(a.id) === String(form.from_account_id)) || null
);
const selectedDest = computed(() =>
  destOptions.value.find(a => String(a.id) === String(form.to_account_id)) || null
);

const sourceCurrency = computed(() => selectedSource.value?.currency ?? '—');
const destCurrency   = computed(() => selectedDest.value?.currency ?? '—');

const sourceBalance  = computed(() => selectedSource.value?.current_balance ?? null);
const destBalance    = computed(() => selectedDest.value?.current_balance ?? null);

const neededFromSource = computed(() => n(form.send_total_amount));
const amountExceedsSource = computed(() => {
  if (!selectedSource.value) return false;
  const bal = n(selectedSource.value.current_balance);
  const amt = n(form.send_actual_amount);
  return amt > 0 && bal > 0 && amt > bal;
});
const totalExceedsSource = computed(() => {
  if (!selectedSource.value) return false;
  const bal = n(selectedSource.value.current_balance);
  const need = neededFromSource.value;
  return need > 0 && bal >= 0 && need > bal;
});

function onFilesChanged(e: Event) {
  const input = e.target as HTMLInputElement;
  if (!input.files) return;
  form.attachments = Array.from(input.files);
}
function removeFile(i: number) { form.attachments.splice(i, 1); }

const canSubmit = computed(() =>
  !!form.type &&
  !!form.name &&
  !!form.category_id &&
  !!form.from_account_id &&
  !!form.to_account_id &&
  n(form.send_actual_amount) > 0 &&
  n(form.receive_actual_amount) > 0
);

function submit() {
  if (!canSubmit.value) return;

  form.transform((data) => {
    const fd = new FormData();
    fd.append('_method', 'PUT');

    fd.append('type', data.type); // long form; server normalizes/stores long

    fd.append('name', data.name ?? '');
    fd.append('category_id', data.category_id ? String(data.category_id) : '');

    fd.append('from_account_id', data.from_account_id ? String(data.from_account_id) : '');
    fd.append('send_actual_amount', String(n(data.send_actual_amount)));
    fd.append('send_total_amount',  String(n(data.send_total_amount)));

    fd.append('to_account_id', data.to_account_id ? String(data.to_account_id) : '');
    fd.append('receive_actual_amount', String(n(data.receive_actual_amount)));
    fd.append('receive_total_amount',  String(n(data.receive_total_amount)));

    fd.append('description', data.description ?? '');

    for (const f of data.attachments as File[]) fd.append('attachments[]', f);

    sourceFees.forEach((f, i) => {
      const hasAny = (f.name ?? '').trim() !== '' || n(f.amount) > 0;
      if (!hasAny) return;
      fd.append(`source_fees[${i}][name]`, f.name ?? '');
      fd.append(`source_fees[${i}][amount]`, String(n(f.amount)));
    });
    destFees.forEach((f, i) => {
      const hasAny = (f.name ?? '').trim() !== '' || n(f.amount) > 0;
      if (!hasAny) return;
      fd.append(`dest_fees[${i}][name]`, f.name ?? '');
      fd.append(`dest_fees[${i}][amount]`, String(n(f.amount)));
    });

    return fd;
  }).post(route('transactions.update', props.transaction.id), {
    preserveScroll: true,
    onFinish: () => form.reset('attachments'),
  });
}

const calc = reactive({ display: '' as string, result: '' as string });
function append(val: string) { calc.display += val; }
function clearAll() { calc.display = ''; calc.result = ''; }
function backspace() { calc.display = calc.display.slice(0, -1); }
function evaluate() {
  try { const out = Function(`"use strict"; return (${calc.display});`)(); calc.result = String(out); }
  catch { calc.result = 'Error'; }
}
</script>

<template>
  <Head title="Edit Transaction" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- LEFT -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Edit Transaction</h1>
            <div class="flex gap-2">
              <Link :href="route('transactions.index')" as="button" class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">Back</Link>
              <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm font-medium text-white"
                :class="canSubmit ? 'bg-gray-900 hover:opacity-90 dark:bg-white dark:text-gray-900' : 'bg-gray-400 cursor-not-allowed'"
                :disabled="form.processing || !canSubmit"
                @click="submit"
              >
                Update
              </button>
            </div>
          </div>

          <form @submit.prevent="submit" class="grid gap-6">
            <!-- Type -->
            <div>
              <label class="mb-1 block text-sm font-medium">Transaction Type <span class="text-red-500">*</span></label>
              <select v-model="txType" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700">
                <option value="income">Income (E/I ➜ Asset)</option>
                <option value="expense">Expense (Asset ➜ E/I)</option>
                <option value="asset">Asset (Asset ➜ Asset)</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
              <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">Overdrafts are permitted. Balances may go negative.</p>
            </div>

            <!-- Name & Category -->
            <div class="grid gap-4 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Transaction Name <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-lg border px-3 py-2"
                  :class="form.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
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

            <!-- SOURCE -->
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
              <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Source (Sender)</h2>

              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium">Source Account <span class="text-red-500">*</span></label>
                  <select
                    v-model="form.from_account_id"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.from_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                  >
                    <option :value="null">— Select Account —</option>
                    <option v-for="a in sourceOptions" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                  <p v-if="form.errors.from_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.from_account_id }}</p>
                  <div class="mt-2 text-xs">
                    <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                      Available: <strong>{{ sourceBalance ?? '—' }}</strong> <span v-if="selectedSource?.currency"> {{ selectedSource?.currency }}</span>
                    </span>
                  </div>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium">
                    Send Amount (Actual)
                    <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{ selectedSource?.currency ?? '—' }}</span>
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.send_actual_amount"
                    type="number" step="0.01" inputmode="decimal"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.send_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                    placeholder="0.00"
                  />
                  <p v-if="form.errors.send_actual_amount" class="mt-1 text-xs text-red-600">{{ form.errors.send_actual_amount }}</p>
                  <p v-else-if="isDepleted(selectedSource)" class="mt-1 text-xs text-amber-600">Source has 0 balance — this will overdraft.</p>
                  <p v-else-if="totalExceedsSource" class="mt-1 text-xs text-amber-600">
                    This will overdraft by {{ (neededFromSource - (Number(sourceBalance ?? 0) || 0)).toFixed(2) }} {{ selectedSource?.currency ?? '' }}.
                  </p>
                  <p v-else-if="amountExceedsSource" class="mt-1 text-xs text-amber-600">Actual exceeds available (before fees).</p>
                </div>
              </div>

              <!-- Sender fees -->
              <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                  <h3 class="text-sm font-medium">Fees (Sender)</h3>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addSourceFee">+ Add Fee</button>
                </div>

                <div class="grid gap-2">
                  <div v-for="(f, i) in sourceFees" :key="i" class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                    <div>
                      <label class="mb-1 block text-xs font-medium">Name</label>
                      <input v-model="f.name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium">Amount</label>
                      <input v-model="f.amount" type="number" step="0.01" inputmode="decimal" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                    </div>
                    <div class="flex justify-end">
                      <button v-if="i > 0" type="button" class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="removeSourceFee(i)">✕</button>
                      <button v-else type="button" class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addSourceFee">+ Add</button>
                    </div>
                  </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="text-sm text-gray-600 dark:text-gray-300">
                    Fees Total: <span class="font-semibold">{{ sourceFeesTotal.toFixed(2) }}</span>
                    <span class="text-xs">{{ selectedSource?.currency ?? '' }}</span>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Send Total (computed)</label>
                    <input :value="form.send_total_amount.toFixed(2)" type="text" readonly class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40" />
                  </div>
                </div>
              </div>
            </div>

            <!-- DESTINATION -->
            <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
              <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Destination (Receiver)</h2>

              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <label class="mb-1 block text-sm font-medium">Destination Account <span class="text-red-500">*</span></label>
                  <select
                    v-model="form.to_account_id"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.to_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                  >
                    <option :value="null">— Select Account —</option>
                    <option v-for="a in destOptions" :key="a.id" :value="a.id">{{ a.name }}</option>
                  </select>
                  <p v-if="form.errors.to_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.to_account_id }}</p>
                  <div class="mt-2 text-xs">
                    <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                      Current: <strong>{{ destBalance ?? '—' }}</strong> <span v-if="selectedDest?.currency"> {{ selectedDest?.currency }}</span>
                    </span>
                  </div>
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium">
                    Receive Amount (Actual)
                    <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{ selectedDest?.currency ?? '—' }}</span>
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.receive_actual_amount"
                    type="number" step="0.01" inputmode="decimal"
                    class="w-full rounded-lg border px-3 py-2"
                    :class="form.errors.receive_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                    placeholder="0.00"
                  />
                  <p v-if="form.errors.receive_actual_amount" class="mt-1 text-xs text-red-600">{{ form.errors.receive_actual_amount }}</p>
                </div>
              </div>

              <!-- Receiver fees -->
              <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                  <h3 class="text-sm font-medium">Fees (Receiver)</h3>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addDestFee">+ Add Fee</button>
                </div>

                <div class="grid gap-2">
                  <div v-for="(f, i) in destFees" :key="i" class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                    <div>
                      <label class="mb-1 block text-xs font-medium">Name</label>
                      <input v-model="f.name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium">Amount</label>
                      <input v-model="f.amount" type="number" step="0.01" inputmode="decimal" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                    </div>
                    <div class="flex justify-end">
                      <button v-if="i > 0" type="button" class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="removeDestFee(i)">✕</button>
                      <button v-else type="button" class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="addDestFee">+ Add</button>
                    </div>
                  </div>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-2">
                  <div class="text-sm text-gray-600 dark:text-gray-300">
                    Fees Total: <span class="font-semibold">{{ destFeesTotal.toFixed(2) }}</span>
                    <span class="text-xs">{{ selectedDest?.currency ?? '' }}</span>
                  </div>
                  <div>
                    <label class="mb-1 block text-sm font-medium">Receive Total (computed)</label>
                    <input :value="form.receive_total_amount.toFixed(2)" type="text" readonly class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40" />
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
                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                placeholder="Optional notes..."
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <!-- Attachments -->
            <div>
              <label class="mb-1 block text-sm font-medium">Attachments</label>

              <!-- Existing -->
              <div v-if="props.transaction.attachments?.length" class="mb-2 rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="border-b border-gray-100 px-3 py-2 text-xs font-semibold text-gray-600 dark:border-gray-800 dark:text-gray-300">
                  Existing files
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                  <li v-for="(p, idx) in props.transaction.attachments" :key="`old-${idx}`" class="px-3 py-2 text-sm">
                    <a :href="`/storage/${p}`" target="_blank" rel="noopener" class="text-blue-600 hover:underline">{{ p }}</a>
                  </li>
                </ul>
                <p class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">New uploads will be appended; existing files remain.</p>
              </div>

              <!-- New uploads -->
              <input type="file" multiple @change="onFilesChanged" class="block w-full cursor-pointer rounded-lg border border-dashed border-gray-300 p-3 text-sm dark:border-gray-700" />
              <ul v-if="form.attachments.length" class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                <li v-for="(f, idx) in form.attachments" :key="`${f.name}-${idx}`" class="flex items-center justify-between px-3 py-2 text-sm">
                  <span class="truncate">{{ f.name }}</span>
                  <button type="button" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800" @click="removeFile(idx)">Remove</button>
                </li>
              </ul>
              <p v-if="form.errors['attachments']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments'] }}</p>
              <p v-if="form.errors['attachments.*']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.*'] }}</p>
            </div>

            <!-- Meta -->
            <div class="grid gap-3 md:grid-cols-2 text-xs text-gray-500 dark:text-gray-400">
              <div v-if="props.transaction.created_at">Created: {{ new Date(props.transaction.created_at).toLocaleString() }}</div>
              <div v-if="props.transaction.updated_at">Updated: {{ new Date(props.transaction.updated_at).toLocaleString() }}</div>
            </div>
          </form>
        </section>

        <!-- RIGHT: Calculator -->
        <aside class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Calculator</h2>
          <div class="space-y-3">
            <input :value="calc.display" readonly class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-lg dark:border-gray-800 dark:bg-gray-900/40" />
            <input :value="calc.result"  readonly class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-base dark:border-gray-800 dark:bg-gray-900/40" placeholder="Result" />
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
              “Send/Receive Total” = <em>Actual + Fees</em>. Overdrafts are allowed.
            </p>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>
