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

type EIType = 'income' | 'expense';
type EIAccount = AccountWithBal & { type: EIType };

type Category = { id: number | string; name: string };

/** ---------- Props (match your create() method) ---------- */
const props = defineProps<{
    categories: Category[];
    accounts: AccountWithBal[]; // Asset accounts
    exp_inc_accounts: EIAccount[]; // Expense/Income accounts with { type }
}>();

/** ---------- Breadcrumbs ---------- */
const breadcrumbs = [{ title: 'Transactions', href: route('transactions.index') }, { title: 'Create' }];

/** ---------- Transaction type ---------- */
type TxType = 'income' | 'expense' | 'asset';
const txType = ref<TxType>('income'); // default

/** ---------- Form model ---------- */
const form = useForm({
    type: txType.value as TxType,

    name: '',
    category_id: null as number | string | null,

    from_account_id: null as number | string | null,
    send_actual_amount: '' as number | string,
    send_total_amount: 0 as number,

    to_account_id: null as number | string | null,
    receive_actual_amount: '' as number | string,
    receive_total_amount: 0 as number,

    description: '' as string | null,
    attachments: [] as File[],
});

/** Reset accounts & keep form.type in sync on change */
watch(txType, () => {
    form.from_account_id = null;
    form.to_account_id = null;
    form.type = txType.value;
});

/** ---------- Helpers ---------- */
const n = (v: unknown) => {
    const x = typeof v === 'string' ? v.replace(/,/g, '') : v;
    const num = Number(x);
    return isFinite(num) ? num : 0;
};
const isDepleted = (acc?: AccountWithBal | null) => !acc || n(acc.current_balance) <= 0;

/** ---------- Options by type (NEW rules) ---------- */
// Income:   From = E/I (ONLY type='income'), To = Assets
// Expense:  From = Assets,                   To = E/I (ONLY type='expense')
// Asset:    From = Assets,                   To = Assets
const sourceOptions = computed<AccountWithBal[]>(() => {
    if (txType.value === 'income') {
        return props.exp_inc_accounts.filter((a) => a.type === 'income');
    }
    return props.accounts;
});
const destOptions = computed<AccountWithBal[]>(() => {
    if (txType.value === 'expense') {
        return props.exp_inc_accounts.filter((a) => a.type === 'expense');
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

/** ---------- Fees (dynamic) ---------- */
type FeeRow = { name: string; amount: number | string };
const sourceFees = reactive<FeeRow[]>([{ name: '', amount: '' }]);
const destFees = reactive<FeeRow[]>([{ name: '', amount: '' }]);

function addSourceFee() {
    sourceFees.push({ name: '', amount: '' });
}
function addDestFee() {
    destFees.push({ name: '', amount: '' });
}
function removeSourceFee(i: number) {
    if (sourceFees.length > 1) sourceFees.splice(i, 1);
}
function removeDestFee(i: number) {
    if (destFees.length > 1) destFees.splice(i, 1);
}

const sourceFeesTotal = computed(() => sourceFees.reduce((s, f) => s + n(f.amount), 0));
const destFeesTotal = computed(() => destFees.reduce((s, f) => s + n(f.amount), 0));

/** ---------- Totals (SUBTRACT fees) ---------- */
watch(
    [() => form.send_actual_amount, sourceFeesTotal],
    () => {
        form.send_total_amount = n(form.send_actual_amount) - sourceFeesTotal.value;
    },
    { immediate: true },
);

watch(
    [() => form.receive_actual_amount, destFeesTotal],
    () => {
        form.receive_total_amount = n(form.receive_actual_amount) - destFeesTotal.value;
    },
    { immediate: true },
);

/** ---------- Overdraft hints (soft) using minus-fee total ---------- */
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

/** ---------- Attachments ---------- */
function onFilesChanged(e: Event) {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    form.attachments = Array.from(input.files);
}
function removeFile(i: number) {
    form.attachments.splice(i, 1);
}

/** ---------- Submit guards ---------- */
const baseRequiredOk = computed(
    () =>
        !!form.type &&
        !!form.name &&
        !!form.category_id &&
        !!form.from_account_id &&
        !!form.to_account_id &&
        n(form.send_actual_amount) > 0 &&
        n(form.receive_actual_amount) > 0,
);
const canSubmit = computed(() => baseRequiredOk.value);

/** ---------- Submit (multipart, includes fees & files) ---------- */
function submit() {
    if (!canSubmit.value) return;

    form.transform((data) => {
        const fd = new FormData();

        fd.append('type', data.type); // controller will normalize to inc/exp/asset

        fd.append('name', data.name ?? '');
        fd.append('category_id', data.category_id ? String(data.category_id) : '');

        fd.append('from_account_id', data.from_account_id ? String(data.from_account_id) : '');
        fd.append('send_actual_amount', String(n(data.send_actual_amount)));
        fd.append('send_total_amount', String(n(data.send_total_amount))); // minus-fee total

        fd.append('to_account_id', data.to_account_id ? String(data.to_account_id) : '');
        fd.append('receive_actual_amount', String(n(data.receive_actual_amount)));
        fd.append('receive_total_amount', String(n(data.receive_total_amount))); // minus-fee total

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
    }).post(route('transactions.store'), {
        preserveScroll: true,
        onFinish: () => form.reset('attachments'),
    });
}

/** ---------- Calculator ---------- */
// --- Calculator (70% size + robust copy + SweetAlert toast if available) ---
const calc = reactive({ display: '' as string, result: '' as string });

// Fallback inline toast state (used when SweetAlert2 isn't present)
const toast = reactive<{ show: boolean; message: string; kind: 'success' | 'error' | 'warning' | 'info' }>({
    show: false,
    message: '',
    kind: 'info',
});

function append(val: string) {
    calc.display += val;
}
function clearAll() {
    calc.display = '';
    calc.result = '';
}
function backspace() {
    calc.display = calc.display.slice(0, -1);
}
function evaluate() {
    try {
        const out = Function(`"use strict"; return (${calc.display});`)();
        calc.result = String(out);
    } catch {
        calc.result = 'Error';
    }
}

// Notify via SweetAlert2 (if available) or fallback to inline toast/alert
function notify(message: string, kind: 'success' | 'error' | 'warning' | 'info' = 'info') {
    const win = window as unknown as { Swal?: any };
    if (win.Swal) {
        win.Swal.fire({
            text: message,
            icon: kind,
            timer: 1200,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
        });
        return;
    }
    // Inline toast fallback
    toast.message = message;
    toast.kind = kind;
    toast.show = true;
    setTimeout(() => (toast.show = false), 1200);
}

async function copyResult() {
    const text = (calc.result ?? '').toString().trim();
    if (!text) {
        notify('Nothing to copy', 'warning');
        return;
    }

    // Try modern clipboard API
    try {
        await navigator.clipboard.writeText(text);
        notify('Copied to clipboard', 'success');
        return;
    } catch {
        // Fallback for older browsers
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            if (ok) {
                notify('Copied to clipboard', 'success');
            } else {
                notify('Copy failed', 'error');
            }
        } catch {
            notify('Copy failed', 'error');
        }
    }
}
</script>

<template>
    <Head title="Create Transaction" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-6 lg:flex-row">
                <!-- LEFT: FORM -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 flex-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h1 class="text-xl font-semibold">New Transaction</h1>
                        <div class="flex gap-2">
                            <Link
                                :href="route('transactions.index')"
                                as="button"
                                class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                Cancel
                            </Link>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-white"
                                :class="
                                    canSubmit ? 'bg-gray-900 hover:opacity-90 dark:bg-white dark:text-gray-900' : 'cursor-not-allowed bg-gray-400'
                                "
                                :disabled="form.processing || !canSubmit"
                                @click="submit"
                            >
                                Save
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="grid gap-6">
                        <!-- Type -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Transaction Type <span class="text-red-500">*</span></label>
                            <VueSelect v-model="txType" class="w-full"
                                :options="[
                                  {label: 'Income (E/I ➜ Asset)', value: 'income'},
                                  {label: 'Expense (Asset ➜ E/I)', value: 'expense'},
                                  {label: 'Asset (Asset ➜ Asset)', value: 'asset'},
                                ]"
                            />
                            <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">Overdrafts are permitted. Balances may go negative.</p>
                        </div>

                        <!-- Name + Category -->
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
                                <VueSelect
                                    v-model="form.category_id"
                                    class="w-full"
                                    :class="form.errors.category_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                    :options="props.categories.map(c => ({label: c.name, value: c.id}))"
                                />
                                <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
                            </div>
                        </div>

                        <!-- SOURCE -->
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Source (Sender)</h2>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium">Source Account <span class="text-red-500">*</span></label>
                                    <VueSelect
                                        v-model="form.from_account_id"
                                        class="w-full"
                                        :class="form.errors.from_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                        :options="sourceOptions.map(a => ({label: a.name, value: a.id}))"
                                    />
                                    <p v-if="form.errors.from_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.from_account_id }}</p>

                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                                            Available: <strong>{{ sourceBalance ?? '—' }}</strong>
                                            <span v-if="sourceCurrency !== '—'">{{ sourceCurrency }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium">
                                        Send Amount (Actual)
                                        <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{ sourceCurrency }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.send_actual_amount"
                                        type="number"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full rounded-lg border px-3 py-2"
                                        :class="form.errors.send_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                        placeholder="0.00"
                                    />
                                    <p v-if="form.errors.send_actual_amount" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.send_actual_amount }}
                                    </p>
                                    <p v-else-if="sourceIsDepleted" class="mt-1 text-xs text-amber-600">
                                        Source has 0 balance — this will overdraft (negative balance).
                                    </p>
                                    <p v-else-if="totalExceedsSource" class="mt-1 text-xs text-amber-600">
                                        This transfer will overdraft the source by
                                        {{ (neededFromSource - n(sourceBalance ?? 0)).toFixed(2) }} {{ sourceCurrency }}.
                                    </p>
                                    <p v-else-if="amountExceedsSource" class="mt-1 text-xs text-amber-600">
                                        Heads-up: actual amount exceeds available balance (before fees).
                                    </p>
                                </div>
                            </div>

                            <!-- Source Fees -->
                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-medium">Fees (Sender)</h3>
                                    <button
                                        type="button"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        @click="addSourceFee"
                                    >
                                        + Add Fee
                                    </button>
                                </div>

                                <div class="grid gap-2">
                                    <div v-for="(f, i) in sourceFees" :key="i" class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Name</label>
                                            <input
                                                v-model="f.name"
                                                type="text"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Amount</label>
                                            <input
                                                v-model="f.amount"
                                                type="number"
                                                step="0.01"
                                                inputmode="decimal"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                                            />
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

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        <!-- wording updated -->
                                        Fees Total (subtracted): <span class="font-semibold">{{ sourceFeesTotal.toFixed(2) }}</span>
                                        <span class="text-xs">{{ sourceCurrency }}</span>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Send Total (Actual − Fees)</label>
                                        <input
                                            :value="form.send_total_amount.toFixed(2)"
                                            type="text"
                                            readonly
                                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DESTINATION -->
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                            <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                Destination (Receiver)
                            </h2>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium">Destination Account <span class="text-red-500">*</span></label>
                                    <VueSelect
                                        v-model="form.to_account_id"
                                        class="w-full"
                                        :class="form.errors.to_account_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                        :options="destOptions.map(a => ({label: a.name, value: a.id}))"
                                    />
                                    <p v-if="form.errors.to_account_id" class="mt-1 text-xs text-red-600">{{ form.errors.to_account_id }}</p>

                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <span class="rounded-md border border-gray-300 px-1.5 py-0.5 text-gray-600">
                                            Current: <strong>{{ destBalance ?? '—' }}</strong>
                                            <span v-if="destCurrency !== '—'">{{ destCurrency }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium">
                                        Receive Amount (Actual)
                                        <span class="ml-1 rounded-md border border-gray-300 px-1 py-0.5 text-xs">{{ destCurrency }}</span>
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.receive_actual_amount"
                                        type="number"
                                        step="0.01"
                                        inputmode="decimal"
                                        class="w-full rounded-lg border px-3 py-2"
                                        :class="form.errors.receive_actual_amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                        placeholder="0.00"
                                    />
                                    <p v-if="form.errors.receive_actual_amount" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.receive_actual_amount }}
                                    </p>
                                </div>
                            </div>

                            <!-- Dest Fees -->
                            <div class="mt-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <h3 class="text-sm font-medium">Fees (Receiver)</h3>
                                    <button
                                        type="button"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        @click="addDestFee"
                                    >
                                        + Add Fee
                                    </button>
                                </div>

                                <div class="grid gap-2">
                                    <div v-for="(f, i) in destFees" :key="i" class="grid items-end gap-2 md:grid-cols-[1fr_160px_auto]">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Name</label>
                                            <input
                                                v-model="f.name"
                                                type="text"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium">Amount</label>
                                            <input
                                                v-model="f.amount"
                                                type="number"
                                                step="0.01"
                                                inputmode="decimal"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                                            />
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

                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        Fees Total (subtracted): <span class="font-semibold">{{ destFeesTotal.toFixed(2) }}</span>
                                        <span class="text-xs">{{ destCurrency }}</span>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Receive Total (Actual − Fees)</label>
                                        <input
                                            :value="form.receive_total_amount.toFixed(2)"
                                            type="text"
                                            readonly
                                            class="w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-900/40"
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
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
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
                            <ul
                                v-if="form.attachments.length"
                                class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800"
                            >
                                <li
                                    v-for="(f, idx) in form.attachments"
                                    :key="`${f.name}-${idx}`"
                                    class="flex items-center justify-between px-3 py-2 text-sm"
                                >
                                    <span class="truncate">{{ f.name }}</span>
                                    <button
                                        type="button"
                                        class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                        @click="removeFile(idx)"
                                    >
                                        Remove
                                    </button>
                                </li>
                            </ul>
                            <p v-if="form.errors['attachments']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments'] }}</p>
                            <p v-if="form.errors['attachments.*']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.*'] }}</p>
                        </div>
                    </form>
                </section>

                <!-- RIGHT: Calculator -->
                <aside class="rounded-xl border border-gray-200 p-4 dark:border-gray-800 flex-1">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Calculator</h2>

                    <!-- 70% scale wrapper -->
                    <div style="">
                        <div class="relative space-y-3">
                            <!-- Inline toast fallback (only shows if SweetAlert isn't available) -->
                            <transition name="fade">
                                <div
                                    v-if="toast.show"
                                    class="absolute -top-2 right-0 z-10 rounded-lg px-3 py-1 text-xs font-medium"
                                    :class="{
                                        'border border-green-200 bg-green-100 text-green-800': toast.kind === 'success',
                                        'border border-red-200 bg-red-100 text-red-800': toast.kind === 'error',
                                        'border border-yellow-200 bg-yellow-100 text-yellow-800': toast.kind === 'warning',
                                        'border border-gray-200 bg-gray-100 text-gray-800': toast.kind === 'info',
                                    }"
                                >
                                    {{ toast.message }}
                                </div>
                            </transition>

                            <input
                                :value="calc.display"
                                readonly
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-lg dark:border-gray-800 dark:bg-gray-900/40"
                            />

                            <!-- Result with Copy button -->
                            <div class="flex items-center gap-2">
                                <input
                                    :value="calc.result"
                                    readonly
                                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-right text-base dark:border-gray-800 dark:bg-gray-900/40"
                                    placeholder="Result"
                                />
                                <button type="button" class="rounded-lg border px-3 py-2 text-sm" @click="copyResult">Copy</button>
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
                                Tip: “Send/Receive Total” are calculated as <em>Actual − Fees</em>. Overdrafts are allowed.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
