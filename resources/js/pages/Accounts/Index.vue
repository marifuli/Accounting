<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';

// ---- Types (optional but nice with TS)
type Account = {
    id: number | string;
    name: string;
    code?: string | null;
    type?: 'bank' | 'card' | 'mobile' | string | null;
    account_number?: string | null;
    is_active?: boolean | null;
    created_at?: string | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type AccountsPaginator = {
    data: Account[];
    current_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

// ---- Read the prop sent by your controller
const props = defineProps<{ accounts: AccountsPaginator }>();

// ---- Breadcrumbs (name matches the template)
const breadcrumbs = [{ title: 'Accounts', href: '/accounts' }];

// ---- Helpers
function fmtDate(iso?: string | null) {
    if (!iso) return '—';
    const d = new Date(iso);
    return isNaN(d.getTime()) ? iso : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' });
}

function visit(link: PaginationLink) {
    if (link.url) router.visit(link.url, { preserveState: true, preserveScroll: true });
}

function destroy(row: { id: number | string; name?: string }) {
    if (!confirm(`Delete account${row.name ? ` “${row.name}”` : ''}?`)) return;
    router.delete(`/accounts/${row.id}`, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Accounts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                <!-- <PlaceholderPattern /> -->
                <div class="h-full w-full p-4">
                    <div class="overflow-x-auto rounded-xl">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Code</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Account No.</th>
                                    <th class="px-4 py-3">Active</th>
                                    <th class="px-4 py-3">Created</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!accounts?.data?.length">
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No accounts found.</td>
                                </tr>

                                <tr
                                    v-for="row in accounts.data"
                                    :key="row.id"
                                    class="border-t border-gray-100 hover:bg-gray-50/60 dark:border-gray-800 dark:hover:bg-gray-900/40"
                                >
                                    <td class="px-4 py-3 font-medium">
                                        <div class="flex flex-col">
                                            <span>{{ row.name }}</span>
                                            <span v-if="row.bank_name" class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ row.bank_name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 tabular-nums">{{ row.code ?? '—' }}</td>
                                    <td class="px-4 py-3 capitalize">{{ row.type ?? '—' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ row.account_number ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                row.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
                                            "
                                        >
                                            <span
                                                class="inline-block h-1.5 w-1.5 rounded-full"
                                                :class="row.is_active ? 'bg-green-500' : 'bg-red-500'"
                                            ></span>
                                            {{ row.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ (row.created_at ?? '').slice(0, 10) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <!-- View -->
                                            <Link
                                                :href="`/accounts/${row.id}`"
                                                as="button"
                                                class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                aria-label="View"
                                                :preserve-state="true"
                                                :preserve-scroll="true"
                                            >
                                                <!-- eye icon -->
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z"
                                                    />
                                                    <circle cx="12" cy="12" r="3" stroke-width="1.5" />
                                                </svg>
                                            </Link>

                                            <!-- Edit -->
                                            <Link
                                                :href="`/accounts/${row.id}/edit`"
                                                as="button"
                                                class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                                                aria-label="Edit"
                                                :preserve-state="true"
                                                :preserve-scroll="true"
                                            >
                                                <!-- pencil icon -->
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M16.862 3.487a2.25 2.25 0 0 1 3.182 3.182L8.25 18.463l-4.5 1.05 1.05-4.5L16.862 3.487z"
                                                    />
                                                </svg>
                                            </Link>

                                            <!-- Delete -->
                                            <button
                                                type="button"
                                                class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                                                aria-label="Delete"
                                                @click="destroy(row)"
                                            >
                                                <!-- trash icon -->
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M3 6h18M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m-8 0l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13M10 11v6m4-6v6"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="accounts?.links?.length" class="mt-4 flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Showing {{ accounts.from ?? 0 }}–{{ accounts.to ?? accounts.data.length }} of {{ accounts.total }}
                        </p>
                        <nav class="flex items-center gap-1">
                            <Link
                                v-for="(link, i) in accounts.links"
                                :key="i"
                                :href="link.url ?? ''"
                                as="button"
                                :disabled="!link.url"
                                :preserve-state="true"
                                :preserve-scroll="true"
                                class="min-w-8 rounded-md px-2 py-1 text-sm"
                                :class="
                                    link.active
                                        ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
                                        : 'border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'
                                "
                            >
                                <span v-html="link.label" />
                            </Link>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
