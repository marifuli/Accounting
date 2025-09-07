<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatDate } from '@/lib/utils';

/** ---------- Types you’ll pass from the controller ---------- */
type MiniAccount = any;

type Fee = { name: string; amount: number | string };

type TxCategory = { id: number | string; name: string } | null;

type TxTypeFE = 'income' | 'expense' | 'asset' | 'inc' | 'exp';

type TransactionShow = {
    id: number | string;
    type: TxTypeFE;
    name: string;
    category: TxCategory;

    from_account: MiniAccount | null; // Asset-side account
    from_ei_account: MiniAccount | null; // E/I-side account (for incomes)

    to_account: MiniAccount | null; // Asset-side account
    to_ei_account: MiniAccount | null; // E/I-side account (for expenses)

    send_actual_amount: number | string;
    send_total_amount: number | string;
    receive_actual_amount: number | string;
    receive_total_amount: number | string;

    description: string | null;
    attachments: string[] | null; // stored paths like "transactions/xxx.pdf"
    source_fees: Fee[]; // type = 'from'
    dest_fees: Fee[]; // type = 'to'

    created_at: string; // ISO
    updated_at: string; // ISO
};

const props = defineProps<{
    transaction: TransactionShow;
}>();

/** ---------- Breadcrumbs ---------- */
const breadcrumbs = [{ title: 'Transactions', href: route('transactions.index') }, { title: 'Show' }];

/** ---------- Helpers ---------- */
const n = (v: unknown) => {
    const x = typeof v === 'string' ? v.replace(/,/g, '') : v;
    const num = Number(x);
    return isFinite(num) ? num : 0;
};

const isIncome = computed(() => props.transaction.type === 'income' || props.transaction.type === 'inc');
const isExpense = computed(() => props.transaction.type === 'expense' || props.transaction.type === 'exp');

/** Decide which records to present based on type */
const source = computed<MiniAccount | null>(() => {
    return props.transaction.from_account;
});
const destination = computed<MiniAccount | null>(() => {
    return props.transaction.to_account;
});

const sourceCurrency = computed(() => source.value?.currency ?? '—');
const destCurrency = computed(() => destination.value?.currency ?? '—');

const sourceBalance = computed(() => source.value?.current_balance ?? null);
const destBalance = computed(() => destination.value?.current_balance ?? null);

const sourceFeesTotal = computed(() => (props.transaction.source_fees ?? []).reduce((sum, f) => sum + n(f.amount), 0));
const destFeesTotal = computed(() => (props.transaction.dest_fees ?? []).reduce((sum, f) => sum + n(f.amount), 0));

function fmtDate(iso?: string | null) {
    if (!iso) return '—';
    return formatDate((iso));
}

function fileUrl(path: string) {
    // Stored via public disk => accessible under /storage/<path>
    return `/storage/${path.replace(/^\/?storage\//, '')}`;
}

function filename(path: string) {
    const idx = path.lastIndexOf('/');
    return idx >= 0 ? path.slice(idx + 1) : path;
}

/** Tags & styles */
const typeLabel = computed(() => {
    const t = props.transaction.type;
    return t === 'income' || t === 'inc' ? 'Income' : t === 'expense' || t === 'exp' ? 'Expense' : 'Asset';
});
const typePillClass = computed(() => {
    if (isIncome.value) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
    if (isExpense.value) return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200';
});

/** Actions */
function destroy() {
    if (!confirm('Delete this transaction?')) return;
    router.delete(route('transactions.destroy', props.transaction.id));
}

function deleteAttachment(i: number) {
    if (!confirm('Delete this attachment?')) return;

    router.delete(
        route('transaction.attachments.destroy', {
            transaction: props.transaction.id,
            index: i,
        }),
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="`Transaction • ${props.transaction.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-6xl p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">
                        {{ props.transaction.name }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="typePillClass">
                            {{ typeLabel }}
                        </span>

                        <span
                            v-if="props.transaction.category"
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
                            title="Category"
                        >
                            {{ props.transaction.category?.name }}
                        </span>

                        <span
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
                            title="Created at"
                        >
                            {{ fmtDate(props.transaction.created_at) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('transactions.index')"
                        as="button"
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        ← Back
                    </Link>
                    <Link
                        :href="route('transactions.edit', props.transaction.id)"
                        as="button"
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        Edit
                    </Link>
                    <button
                        class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                        @click="destroy"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <!-- Overview Cards -->
            <div class="mb-6 grid gap-6 md:grid-cols-2">
                <!-- Source (Sender) -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Source (Sender)</h2>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">Account</dt>
                        <dd class="col-span-2">
                            {{ source?.name ?? '—' }}
                            <span v-if="sourceCurrency !== '—'" class="ml-2 rounded-md border border-gray-300 px-1 py-0.5 text-[10px]">{{
                                sourceCurrency
                            }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Available</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ sourceBalance ?? '—' }}</span>
                            <span v-if="sourceCurrency !== '—'">{{ ' ' + sourceCurrency }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Send (Actual)</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ props.transaction.send_actual_amount }}</span>
                            <span v-if="sourceCurrency !== '—'">{{ ' ' + sourceCurrency }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Send (Total)</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ props.transaction.send_total_amount }}</span>
                            <span v-if="sourceCurrency !== '—'">{{ ' ' + sourceCurrency }}</span>
                        </dd>
                    </dl>

                    <!-- Sender Fees -->
                    <div class="mt-4">
                        <h3 class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Fees (Sender)</h3>
                        <div v-if="(props.transaction.source_fees?.length ?? 0) === 0" class="text-xs text-gray-500">—</div>
                        <ul
                            v-else
                            class="divide-y divide-gray-100 rounded-lg border border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800"
                        >
                            <li
                                v-for="(f, i) in props.transaction.source_fees"
                                :key="`sf-${i}-${f.name}`"
                                class="flex items-center justify-between px-3 py-2"
                            >
                                <span class="truncate">{{ f.name }}</span>
                                <span class="font-mono">
                                    {{ f.amount }}
                                    <span v-if="sourceCurrency !== '—'">{{ sourceCurrency }}</span>
                                </span>
                            </li>
                            <li class="flex items-center justify-between bg-gray-50 px-3 py-2 text-xs dark:bg-gray-900/40">
                                <span class="font-medium">Total</span>
                                <span class="font-mono">
                                    {{ sourceFeesTotal.toFixed(2) }}
                                    <span v-if="sourceCurrency !== '—'">{{ sourceCurrency }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Destination (Receiver) -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Destination (Receiver)</h2>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">Account</dt>
                        <dd class="col-span-2">
                            {{ destination?.name ?? '—' }}
                            <span v-if="destCurrency !== '—'" class="ml-2 rounded-md border border-gray-300 px-1 py-0.5 text-[10px]">{{
                                destCurrency
                            }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Current</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ destBalance ?? '—' }}</span>
                            <span v-if="destCurrency !== '—'">{{ ' ' + destCurrency }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Receive (Actual)</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ props.transaction.receive_actual_amount }}</span>
                            <span v-if="destCurrency !== '—'">{{ ' ' + destCurrency }}</span>
                        </dd>

                        <dt class="text-gray-500 dark:text-gray-400">Receive (Total)</dt>
                        <dd class="col-span-2">
                            <span class="font-mono">{{ props.transaction.receive_total_amount }}</span>
                            <span v-if="destCurrency !== '—'">{{ ' ' + destCurrency }}</span>
                        </dd>
                    </dl>

                    <!-- Receiver Fees -->
                    <div class="mt-4">
                        <h3 class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Fees (Receiver)</h3>
                        <div v-if="(props.transaction.dest_fees?.length ?? 0) === 0" class="text-xs text-gray-500">—</div>
                        <ul
                            v-else
                            class="divide-y divide-gray-100 rounded-lg border border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800"
                        >
                            <li
                                v-for="(f, i) in props.transaction.dest_fees"
                                :key="`df-${i}-${f.name}`"
                                class="flex items-center justify-between px-3 py-2"
                            >
                                <span class="truncate">{{ f.name }}</span>
                                <span class="font-mono">
                                    {{ f.amount }}
                                    <span v-if="destCurrency !== '—'">{{ destCurrency }}</span>
                                </span>
                            </li>
                            <li class="flex items-center justify-between bg-gray-50 px-3 py-2 text-xs dark:bg-gray-900/40">
                                <span class="font-medium">Total</span>
                                <span class="font-mono">
                                    {{ destFeesTotal.toFixed(2) }}
                                    <span v-if="destCurrency !== '—'">{{ destCurrency }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>

            <!-- Meta / Description / Attachments -->
            <div class="grid gap-6 md:grid-cols-2">
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Details</h2>
                    <dl class="grid grid-cols-3 gap-3 text-sm">
                        <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="col-span-2">{{ typeLabel }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">Category</dt>
                        <dd class="col-span-2">{{ props.transaction.category?.name ?? '—' }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="col-span-2">{{ fmtDate(props.transaction.created_at) }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
                        <dd class="col-span-2">{{ fmtDate(props.transaction.updated_at) }}</dd>

                        <dt class="text-gray-500 dark:text-gray-400">Description</dt>
                        <dd class="col-span-2 whitespace-pre-wrap">{{ props.transaction.description ?? '—' }}</dd>
                    </dl>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Attachments</h2>
                    <div v-if="!props.transaction.attachments || props.transaction.attachments.length === 0" class="text-sm text-gray-500">—</div>
                    <ul v-else class="divide-y divide-gray-100 rounded-lg border border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800">
                        <li v-for="(p, i) in props.transaction.attachments" :key="`${p}-${i}`" class="flex items-center justify-between px-3 py-2">
                            <span class="truncate">{{ filename(p) }}</span>
                            <div class="flex items-center gap-2">
                                <a
                                    :href="fileUrl(p)" download
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    View
                                </a>
                                <button
                                    type="button"
                                    class="rounded-md border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                                    @click="deleteAttachment(i)"
                                    title="Delete attachment"
                                >
                                    Delete
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
