<!-- resources/js/pages/UpcommingExpenseIncome/Create.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import VueSelect from "vue3-select-component";

type ExpIncAccount = { id: number | string; name: string };

const props = defineProps<{
    expIncAccounts: ExpIncAccount[];
}>();

const breadcrumbs = [{ title: 'Upcoming E/I', href: '/upcomming-expense-income' }, { title: 'Create' }];

const form = useForm({
    title: '',
    description: '' as string | null,
    eia_id: null as number | string | null,
    date: '' as string, // YYYY-MM-DD
    type: 'expense' as 'income' | 'expense',
    // NEW
    amount: '' as string | number,
    currency: 'BDT' as
        | 'USD' | 'EUR' | 'GBP' | 'JPY' | 'AUD' | 'CAD' | 'CHF' | 'CNY'
        | 'INR' | 'BRL' | 'ZAR' | 'BDT' | 'other',

    attachments: [] as File[],
});

function onFilesChanged(e: Event) {
    const input = e.target as HTMLInputElement;
    if (!input.files) return;
    form.attachments = Array.from(input.files);
}

function removeFile(idx: number) {
    form.attachments.splice(idx, 1);
}

function submit() {
    form.transform((data) => {
        const fd = new FormData();

        fd.append('title', data.title ?? '');
        if (data.description) fd.append('description', data.description);
        if (data.date) fd.append('date', data.date);
        fd.append('type', data.type);

        // NEW
        fd.append('amount', String(data.amount ?? '0'));
        fd.append('currency', String(data.currency));

        if (data.eia_id !== null && data.eia_id !== '' && data.eia_id !== undefined) {
            fd.append('eia_id', String(data.eia_id));
        }

        (data.attachments as File[]).forEach((file, i) => {
            fd.append(`attachments[${i}]`, file);
        });

        return fd;
    }).post('/upcomming-expense-income', {
        preserveScroll: true,
        onFinish: () => form.reset('attachments'),
    });
}
</script>

<template>
    <Head title="Create Upcoming Income/Expense" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold">New Upcoming Income/Expense</h1>
                <div class="flex gap-2">
                    <Link
                        href="/upcomming-expense-income"
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
                <!-- Details -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-4 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Details</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Title <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="form.errors.title ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                placeholder="e.g. House Rent, Salary (Nov), Utility Bill"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Date <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.date"
                                type="date"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="form.errors.date ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                            />
                            <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Type <span class="text-red-500">*</span></label>
                            <VueSelect v-model="form.type" class="w-full"
                                :options="[{label: 'Expense', value: 'expense'}, {label: 'Income', value: 'income'}]"
                            />
                            <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                        </div>

                        <!-- NEW: Amount -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Amount <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0"
                                inputmode="decimal"
                                class="w-full rounded-lg border px-3 py-2"
                                :class="form.errors.amount ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                placeholder="0.00"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <!-- NEW: Currency -->
                        <div>
                            <label class="mb-1 block text-sm font-medium">Currency <span class="text-red-500">*</span></label>
                            <VueSelect
                                v-model="form.currency"
                                class="w-full"
                                :class="form.errors.currency ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                :options="[
                                    {label: 'BDT', value: 'BDT'},
                                    {label: 'USD', value: 'USD'},
                                    {label: 'EUR', value: 'EUR'},
                                    {label: 'GBP', value: 'GBP'},
                                    {label: 'JPY', value: 'JPY'},
                                    {label: 'AUD', value: 'AUD'},
                                    {label: 'CAD', value: 'CAD'},
                                    {label: 'CHF', value: 'CHF'},
                                    {label: 'CNY', value: 'CNY'},
                                    {label: 'INR', value: 'INR'},
                                    {label: 'BRL', value: 'BRL'},
                                    {label: 'ZAR', value: 'ZAR'},
                                    {label: 'Other', value: 'other'},
                                ]"
                            />
                            <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">{{ form.errors.currency }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Linked Account (optional)</label>
                            <VueSelect
                                v-model="form.eia_id"
                                class="w-full"
                                :class="form.errors.eia_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                                :options="[{label: '— None —', value: null}, ...props.expIncAccounts.map(acc => ({label: acc.name, value: acc.id}))]"
                            />
                            <p v-if="form.errors.eia_id" class="mt-1 text-xs text-red-600">{{ form.errors.eia_id }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700"
                                placeholder="Optional notes..."
                            />
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </section>

                <!-- Attachments (unchanged) -->
                <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                    <h2 class="mb-4 text-sm font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Attachments</h2>
                    <div class="grid gap-3">
                        <input
                            type="file"
                            multiple
                            @change="onFilesChanged"
                            class="block w-full cursor-pointer rounded-lg border border-dashed border-gray-300 p-3 text-sm dark:border-gray-700"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400">You can attach invoices, bills, receipts, etc. (optional)</p>

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
                        <!-- <p v-if="form.errors['attachments.*']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.*'] }}</p> -->
                    </div>
                </section>
            </form>
        </div>
    </AppLayout>
</template>
