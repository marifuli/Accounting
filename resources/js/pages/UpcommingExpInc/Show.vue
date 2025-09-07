<!-- resources/js/Pages/UpcommingExpInc/Show.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatDate } from '@/lib/utils';
function fmtDate(iso?: string | null) {
    if (!iso) return '—';
    return formatDate((iso));
}
type UpItem = {
    id: number | string;
    title: string;
    description?: string | null;
    eia_id?: number | string | null;
    date?: string | null; // "YYYY-MM-DD"
    type?: 'income' | 'expense' | string | null;
    attachments?: string[] | null; // e.g. ["upcoming/abc.pdf", ...]
    eia?: { id: number | string; name: string } | null;
    // NEW:
    amount?: string | number | null;
    currency?: string | null;
};

const props = defineProps<{
    upcomming_expense_income: UpItem;
}>();

const item = props.upcomming_expense_income;

const breadcrumbs = [{ title: 'Upcoming E/I', href: route('upcomming-expense-income.index') }, { title: 'Show' }];

const typeLabel = computed(() => (item.type === 'income' ? 'Income' : item.type === 'expense' ? 'Expense' : (item.type ?? '—')));

const typePillClass = computed(() => {
    switch (item.type) {
        case 'income':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';
        case 'expense':
            return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
    }
});

// NEW: format "BDT 1,234.50"
function fmtMoney(amount?: string | number | null, currency?: string | null) {
    if (amount === null || amount === undefined || amount === '') return '—';
    const n = Number(amount);
    if (Number.isNaN(n)) return String(amount);
    const code = (currency ?? 'BDT').toUpperCase();
    const formatted = n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return `${code} ${formatted}`;
}

function fileUrl(path: string) {
    return `/storage/${String(path).replace(/^\/?storage\//, '')}`;
}
function filename(path: string) {
    const i = path.lastIndexOf('/');
    return i >= 0 ? path.slice(i + 1) : path;
}

/**
 * Local, reactive attachments list so UI updates immediately after delete.
 * Keep it in sync with props if the server rehydrates.
 */
const attachments = ref<string[]>([...(item.attachments ?? [])]);
watch(
    () => props.upcomming_expense_income.attachments,
    (next) => {
        attachments.value = [...(next ?? [])];
    },
);

function destroyAttachment(index: number) {
    if (!confirm('Delete this attachment?')) return;

    // optimistic update
    const snapshot = attachments.value.slice();
    attachments.value.splice(index, 1);

    router.delete(
        route('upcomming-expense-income.attachments.destroy', {
            upcomming_expense_income: item.id,
            index,
        }),
        {
            preserveScroll: true,
            onError: () => {
                // revert on error
                attachments.value = snapshot;
            },
            // onSuccess: () => router.reload({ only: ['upcomming_expense_income'], preserveScroll: true }),
        },
    );
}
</script>

<template>
    <Head :title="`Upcoming • ${item.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">{{ item.title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="typePillClass">
                            {{ typeLabel }}
                        </span>
                        <span
                            v-if="item.date"
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
                        >
                            {{ fmtDate(item.date) }}
                        </span>
                        <span
                            v-if="item.eia?.name"
                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
                        >
                            {{ item.eia.name }}
                        </span>

                        <!-- NEW: Amount badge -->
                        <span
                            v-if="item.amount !== undefined && item.amount !== null && item.amount !== ''"
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                item.type === 'income'
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200'
                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200'
                            "
                        >
                            {{ fmtMoney(item.amount, item.currency) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('upcomming-expense-income.index')"
                        as="button"
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        ← Back
                    </Link>
                    <Link
                        :href="route('upcomming-expense-income.edit', item.id)"
                        as="button"
                        class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        Edit
                    </Link>
                </div>
            </div>

            <!-- Details -->
            <section class="mb-6 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Details</h2>
                <dl class="grid grid-cols-3 gap-3 text-sm">
                    <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                    <dd class="col-span-2">{{ typeLabel }}</dd>

                    <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                    <dd class="col-span-2">{{ fmtDate(item.date) }}</dd>

                    <dt class="text-gray-500 dark:text-gray-400">Linked Account</dt>
                    <dd class="col-span-2">{{ item.eia?.name ?? '—' }}</dd>

                    <!-- NEW: Amount & Currency -->
                    <dt class="text-gray-500 dark:text-gray-400">Amount</dt>
                    <dd class="col-span-2">
                        {{ fmtMoney(item.amount, item.currency) }}
                    </dd>

                    <dt class="text-gray-500 dark:text-gray-400">Currency</dt>
                    <dd class="col-span-2">{{ (item.currency ?? 'BDT').toUpperCase() }}</dd>

                    <dt class="text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="col-span-2 whitespace-pre-wrap">{{ item.description ?? '—' }}</dd>
                </dl>
            </section>

            <!-- Attachments -->
            <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h2 class="mb-3 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Attachments</h2>

                <div v-if="attachments.length === 0" class="text-sm text-gray-500">—</div>

                <ul v-else class="divide-y divide-gray-100 rounded-lg border border-gray-200 text-sm dark:divide-gray-800 dark:border-gray-800">
                    <li v-for="(p, i) in attachments" :key="`${p}-${i}`" class="flex items-center justify-between gap-2 px-3 py-2">
                        <span class="truncate">{{ filename(p) }}</span>
                        <div class="flex gap-2">
                            <a
                                :href="fileUrl(p)"
                                target="_blank" download
                                rel="noopener"
                                class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                            >
                                View
                            </a>
                            <button
                                type="button"
                                class="rounded-md border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                                @click="destroyAttachment(i)"
                            >
                                Delete
                            </button>
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
