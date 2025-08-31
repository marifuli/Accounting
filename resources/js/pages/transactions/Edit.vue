<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import VueSelect from "vue3-select-component";

/** ---------- Types from controller payload ---------- */
type AccountWithBal = {
    id: number | string;
    name: string;
    currency: string | null;
    current_balance: string | number | null;
};

// E/I accounts include 'type' so we can filter (income|expense)
type EIAWithType = AccountWithBal & { type: 'income' | 'expense' };

type Category = { id: number | string; name: string };
type FeeRow = { name: string; amount: number | string };

type TxType = 'income' | 'expense' | 'asset';

const props = defineProps<{
    transaction: {
        id: number | string;
        type: TxType; // long form for UI
        name: string;
        category_id: number | string | null;
        from_account_id: number | string | null;
        to_account_id: number | string | null;
        description: string | null;
        attachments: string[];

        send_total_amount: number | string;
        send_actual_amount: number | string;
        receive_total_amount: number | string;
        receive_actual_amount: number | string;

        created_at?: string | null;
        updated_at?: string | null;
    };
    source_fees: FeeRow[];
    dest_fees: FeeRow[];
    categories: Category[];
    accounts: AccountWithBal[];           // Asset accounts
    exp_inc_accounts: EIAWithType[];      // E/I accounts WITH type
}>();

const breadcrumbs = [{ title: 'Transactions', href: route('transactions.index') }, { title: 'Edit' }];

/** ---------- Type + Form ---------- */
const txType = ref<TxType>(props.transaction.type);

const form = useForm({
    type: txType.value as TxType,

    name: props.transaction.name ?? '',
    category_id: props.transaction.category_id,

    from_account_id: props.transaction.from_account_id,
    send_actual_amount: props.transaction.send_actual_amount,
    send_total_amount: Number(props.transaction.send_total_amount) || 0,

    to_account_id: props.transaction.to_account_id,
    receive_actual_amount: props.transaction.receive_actual_amount,
    receive_total_amount: Number(props.transaction.receive_total_amount) || 0,

    description: props.transaction.description ?? '',
    // New uploads only; existing attachments are shown separately
    attachments: [] as File[],
});

watch(txType, () => {
    form.type = txType.value;
    // Reset accounts on type switch
    form.from_account_id = null;
    form.to_account_id = null;
});

/** ---------- Helpers ---------- */
const n = (v: unknown) => {
    const x = typeof v === 'string' ? v.replace(/,/g, '') : v;
    const num = Number(x);
    return isFinite(num) ? num : 0;
};
const isDepleted = (acc?: AccountWithBal | null) => !acc || n(acc.current_balance) <= 0;

/** ---------- Options by type (SAME RULES AS CREATE) ---------- */
// Income:  From = E/I (ONLY 'income' type), To = Asset accounts
// Expense: From = Asset accounts,          To = E/I (ONLY 'expense' type)
// Asset:   From = Asset accounts,          To = Asset accounts
const sourceOptions = computed<AccountWithBal[]>(() => {
    if (txType.value === 'income') {
        return props.exp_inc_accounts.filter(a => a.type === 'income');
    }
    return props.accounts;
});
const destOptions = computed<AccountWithBal[]>(() => {
    if (txType.value === 'expense') {
        return props.exp_inc_accounts.filter(a => a.type === 'expense');
    }
    return props.accounts;
});

/** Selected accounts & info */
const selectedSource = computed(() => sourceOptions.value.find((a) => String(a.id) === String(form.from_account_id)) || null);
const selectedDest = computed(() => destOptions.value.find((a) => String(a.id) === String(form.to_account_id)) || null);

const sourceCurrency = computed(() => selectedSource.value?.currency ?? '—');
const destCurrency = computed(() => selectedDest.value?.currency ?? '—');

const sourceBalance = computed(() => selectedSource.value?.current_balance ?? null);
const destBalance = computed(() => selectedDest.value?.current_balance ?? null);

const sourceIsDepleted = computed(() => isDepleted(selectedSource.value));

/** ---------- Fees (editable) ---------- */
const sourceFees = reactive<FeeRow[]>(props.source_fees?.length ? [...props.source_fees] : [{ name: '', amount: '' }]);
const destFees = reactive<FeeRow[]>(props.dest_fees?.length ? [...props.dest_fees] : [{ name: '', amount: '' }]);

function addSourceFee() { sourceFees.push({ name: '', amount: '' }); }
function addDestFee() { destFees.push({ name: '', amount: '' }); }
function removeSourceFee(i: number) { if (sourceFees.length > 1) sourceFees.splice(i, 1); }
function removeDestFee(i: number) { if (destFees.length > 1) destFees.splice(i, 1); }

const sourceFeesTotal = computed(() => sourceFees.reduce((s, f) => s + n(f.amount), 0));
const destFeesTotal = computed(() => destFees.reduce((s, f) => s + n(f.amount), 0));

// ✅ FEES ARE MINUS: totals = actual - fees
watch(
    [() => form.send_actual_amount, sourceFeesTotal],
    () => { form.send_total_amount = n(form.send_actual_amount) - sourceFeesTotal.value; },
    { immediate: true },
);
watch(
    [() => form.receive_actual_amount, destFeesTotal],
    () => { form.receive_total_amount = n(form.receive_actual_amount) - destFeesTotal.value; },
    { immediate: true },
);

/** ---------- Attachments (existing list + add new) ---------- */
const existingAttachments = ref<string[]>(props.transaction.attachments ?? []);
function onFilesChanged(e: Event) {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    form.attachments = Array.from(input.files);
}
function fileUrl(path: string) {
    return `/storage/${path.replace(/^\/?storage\//, '')}`;
}
function filename(path: string) {
    const idx = path.lastIndexOf('/');
    return idx >= 0 ? path.slice(idx + 1) : path;
}

/** ---------- Submit ---------- */
function submit() {
    form.transform((data) => {
        const fd = new FormData();

        fd.append('type', data.type);

        fd.append('name', data.name ?? '');
        fd.append('category_id', data.category_id ? String(data.category_id) : '');

        fd.append('from_account_id', data.from_account_id ? String(data.from_account_id) : '');
        fd.append('send_actual_amount', String(n(data.send_actual_amount)));
        fd.append('send_total_amount', String(n(data.send_total_amount)));

        fd.append('to_account_id', data.to_account_id ? String(data.to_account_id) : '');
        fd.append('receive_actual_amount', String(n(data.receive_actual_amount)));
        fd.append('receive_total_amount', String(n(data.receive_total_amount)));

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

        // Spoof method for PUT
        fd.append('_method', 'PUT');

        return fd;
    }).post(route('transactions.update', props.transaction.id), {
        preserveScroll: true,
        onFinish: () => form.reset('attachments'),
    });
}

/** ---------- Calculator (70%) with Copy + toast ---------- */
const calc = reactive({ display: '' as string, result: '' as string });
const toast = reactive({ show: false, text: '' as string });

function append(val: string) { calc.display += val; }
function clearAll() { calc.display = ''; calc.result = ''; }
function backspace() { calc.display = calc.display.slice(0, -1); }
function evaluate() {
    try {
        // eslint-disable-next-line no-new-func
        const out = Function(`"use strict"; return (${calc.display});`)();
        calc.result = String(out);
    } catch {
        calc.result = 'Error';
    }
}
async function copyResult() {
    try {
        await navigator.clipboard.writeText(calc.result || '');
        toast.text = 'Copied!';
    } catch {
        toast.text = 'Copy failed';
    }
    toast.show = true;
    setTimeout(() => (toast.show = false), 1200);
}
</script>

<template>

    <Head :title="`Edit Transaction • ${props.transaction.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- LEFT: FORM -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 flex-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-xl font-semibold">Edit Transaction</h1>
                        <div class="flex gap-2">
                            <Link :href="route('transactions.index')" as="button"
                                class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
                            Cancel
                            </Link>
                            <button type="button"
                                class="rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:opacity-90 dark:bg-white dark:text-gray-900"
                                :disabled="form.processing" @click="submit">
                                Update
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="grid gap-6">
                        <!-- Type -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Transaction Type <span
                                    class="text-red-500">*</span></label>
                            <VueSelect v-model="txType"
                                class="w-full"
                                :options="[
                                  {label: 'Income (E/I ➜ Asset)', value: 'income'},
                                  {label: 'Expense (Asset ➜ E/I)', value: 'expense'},
                                  {label: 'Asset (Asset ➜ Asset)', value: 'asset'},
                                ]"
                            />
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                Fees are subtracted from actual amounts. Overdrafts are permitted.
                            </p>
                        </div>

                        <!-- Name + Category -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium">Transaction Name <span
                                        class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" class="w-full rounded-lg border px-3 py-2" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">Category <span
                                        class="text-red-500">*</span></label>
                                <VueSelect v-model="form.category_id" class="w-full"
                                    :options="props.categories.map(c => ({label: c.name, value: c.id}))"
                                />
                            </div>
                        </div>

                        <!-- SOURCE -->
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <h2
                                class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Source (Sender)</h2>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium">Source Account <span
                                            class="text-red-500">*</span></label>
                                    <VueSelect v-model="form.from_account_id" class="w-full"
                                        :options="sourceOptions.map(a => ({label: a.name, value: a.id}))"
                                    />
                                    <div class="mt-2 text-xs">
                                        <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                                            Available: <strong>{{ sourceBalance ?? '—' }}</strong>
                                            <span v-if="sourceCurrency !== '—'">{{ sourceCurrency }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium">
                                        Send Amount (Actual)
                                        <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{
                                            sourceCurrency }}</span>
                                    </label>
                                    <input v-model="form.send_actual_amount" type="number" step="0.01"
                                        inputmode="decimal" class="w-full rounded-lg border px-3 py-2" />
                                </div>
                            </div>

                            <!-- Source Fees -->
                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-medium">Fees (Sender)</h3>
                                    <button type="button"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        @click="addSourceFee">
                                        + Add Fee
                                    </button>
                                </div>

                                <div class="grid gap-2">
                                    <div v-for="(f, i) in sourceFees" :key="i"
                                        class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Name</label>
                                            <input v-model="f.name" type="text"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Amount</label>
                                            <input v-model="f.amount" type="number" step="0.01" inputmode="decimal"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                                        </div>
                                        <div class="flex justify-end">
                                            <button v-if="i > 0" type="button"
                                                class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                @click="removeSourceFee(i)">✕</button>
                                            <button v-else type="button"
                                                class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                @click="addSourceFee">+ Add</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Fees Total: <span class="font-semibold">{{ sourceFeesTotal.toFixed(2) }}</span>
                                        <span class="text-xs">{{ sourceCurrency }}</span>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Send Total (computed)</label>
                                        <input :value="(n(form.send_actual_amount) - sourceFeesTotal).toFixed(2)"
                                            type="text" readonly
                                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DESTINATION -->
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <h2
                                class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Destination (Receiver)</h2>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium">Destination Account <span
                                            class="text-red-500">*</span></label>
                                    <VueSelect v-model="form.to_account_id" class="w-full"
                                        :options="destOptions.map(a => ({label: a.name, value: a.id}))"
                                    />
                                    <div class="mt-2 text-xs">
                                        <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                                            Current: <strong>{{ destBalance ?? '—' }}</strong>
                                            <span v-if="destCurrency !== '—'">{{ destCurrency }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium">
                                        Receive Amount (Actual)
                                        <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{
                                            destCurrency }}</span>
                                    </label>
                                    <input v-model="form.receive_actual_amount" type="number" step="0.01"
                                        inputmode="decimal" class="w-full rounded-lg border px-3 py-2" />
                                </div>
                            </div>

                            <!-- Dest Fees -->
                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-medium">Fees (Receiver)</h3>
                                    <button type="button"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        @click="addDestFee">+ Add Fee</button>
                                </div>

                                <div class="grid gap-2">
                                    <div v-for="(f, i) in destFees" :key="i"
                                        class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Name</label>
                                            <input v-model="f.name" type="text"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Amount</label>
                                            <input v-model="f.amount" type="number" step="0.01" inputmode="decimal"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                                        </div>
                                        <div class="flex justify-end">
                                            <button v-if="i > 0" type="button"
                                                class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                @click="removeDestFee(i)">✕</button>
                                            <button v-else type="button"
                                                class="rounded-md border px-2 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                @click="addDestFee">+ Add</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Fees Total: <span class="font-semibold">{{ destFeesTotal.toFixed(2) }}</span>
                                        <span class="text-xs">{{ destCurrency }}</span>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Receive Total (computed)</label>
                                        <input :value="(n(form.receive_actual_amount) - destFeesTotal).toFixed(2)"
                                            type="text" readonly
                                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Description</label>
                            <textarea v-model="form.description" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700" />
                        </div>

                        <!-- Attachments -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Existing Attachments</label>
                            <div v-if="!existingAttachments.length" class="text-sm text-gray-500">—</div>
                            <ul v-else
                                class="mb-3 divide-y divide-gray-100 rounded-lg border border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800">
                                <li v-for="(p, i) in existingAttachments" :key="`${p}-${i}`"
                                    class="flex items-center justify-between px-3 py-2">
                                    <span class="truncate">{{ filename(p) }}</span>
                                    <a :href="fileUrl(p)" target="_blank" rel="noopener"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">View</a>
                                </li>
                            </ul>

                            <label class="mb-1 block text-sm font-medium">Add New Attachments</label>
                            <input type="file" multiple @change="onFilesChanged"
                                class="block w-full cursor-pointer rounded-lg border border-dashed border-gray-300 p-3 text-sm dark:border-gray-700" />
                            <ul v-if="form.attachments.length"
                                class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                <li v-for="(f, idx) in form.attachments" :key="`${f.name}-${idx}`"
                                    class="flex items-center justify-between px-3 py-2 text-sm">
                                    <span class="truncate">{{ f.name }}</span>
                                </li>
                            </ul>
                        </div>
                    </form>
                </section>

                <!-- RIGHT: Calculator (70%) -->
                <aside class="relative rounded-xl border border-gray-200 p-4 dark:border-gray-800 flex-1">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                        Calculator</h2>

                    <!-- scale to 70% while keeping layout nice -->
                    <div class="">
                        <div class="space-y-3">
                            <input :value="calc.display" readonly
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-lg dark:border-gray-800 dark:bg-gray-900/40" />
                            <div class="relative">
                                <input :value="calc.result" readonly
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-base pr-16 dark:border-gray-800 dark:bg-gray-900/40"
                                    placeholder="Result" />
                                <button type="button"
                                    class="absolute inset-y-0 right-2 my-auto h-8 rounded-md border px-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                    @click="copyResult">
                                    Copy
                                </button>
                            </div>

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
                                Tip: Totals = <em>Actual − Fees</em>. Overdrafts are allowed.
                            </p>
                        </div>
                    </div>

                    <!-- tiny toast (sweetalert-style) -->
                    <transition name="fade">
                        <div v-if="toast.show"
                            class="pointer-events-none absolute right-3 top-3 rounded-md bg-gray-900/90 px-3 py-1.5 text-xs text-white shadow">
                            {{ toast.text }}
                        </div>
                    </transition>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity .15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
