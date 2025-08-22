<script setup lang="ts">
type Direction = 'asc' | 'desc';

export type Account = {
  id: number | string;
  name: string;
  code?: string | null;
  type?: 'bank' | 'card' | 'mobile' | string | null;
  is_active?: boolean | null;
  account_number?: string | null;
  bank_name?: string | null;
  created_at?: string | null; // ISO string from backend
  [key: string]: unknown;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type PaginationMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page?: number;
  from?: number;
  to?: number;
  links?: PaginationLink[];
};

const props = defineProps<{
  rows: Account[];
  loading?: boolean;
  // Server-driven sort (parent should refetch on change)
  sort?: { field: string; direction: Direction } | null;
  // Optional paginator from Laravel (Inertia) response
  pagination?: PaginationMeta | null;
  // Toggles for action buttons
  canView?: boolean;
  canEdit?: boolean;
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  (e: 'sort-change', payload: { field: string; direction: Direction }): void;
  (e: 'page-change', url: string): void;
  (e: 'view', row: Account): void;
  (e: 'edit', row: Account): void;
  (e: 'delete', row: Account): void;
}>();

function sortIcon(field: string) {
  if (!props.sort || props.sort.field !== field) return '⇅';
  return props.sort.direction === 'asc' ? '▲' : '▼';
}

function onSort(field: string) {
  const next: Direction =
    props.sort && props.sort.field === field && props.sort.direction === 'asc'
      ? 'desc'
      : 'asc';
  emit('sort-change', { field, direction: next });
}

function onPage(link: PaginationLink) {
  if (link.url) emit('page-change', link.url);
}

function fmtDate(iso?: string | null) {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso; // fallback
  return d.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  });
}
</script>

<template>
  <div class="w-full">
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
          <tr>
            <th class="px-4 py-3 cursor-pointer select-none" @click="onSort('name')">
              <span class="inline-flex items-center gap-2">Name <span class="text-xs opacity-70">{{ sortIcon('name') }}</span></span>
            </th>
            <th class="px-4 py-3 cursor-pointer select-none" @click="onSort('code')">
              <span class="inline-flex items-center gap-2">Code <span class="text-xs opacity-70">{{ sortIcon('code') }}</span></span>
            </th>
            <th class="px-4 py-3 cursor-pointer select-none" @click="onSort('type')">
              <span class="inline-flex items-center gap-2">Type <span class="text-xs opacity-70">{{ sortIcon('type') }}</span></span>
            </th>
            <th class="px-4 py-3">Account No.</th>
            <th class="px-4 py-3">Active</th>
            <th class="px-4 py-3 cursor-pointer select-none" @click="onSort('created_at')">
              <span class="inline-flex items-center gap-2">Created <span class="text-xs opacity-70">{{ sortIcon('created_at') }}</span></span>
            </th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading state -->
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6">
              <div class="animate-pulse h-4 w-1/3 bg-gray-200 dark:bg-gray-700 rounded"></div>
              <div class="mt-2 animate-pulse h-4 w-1/4 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-else-if="!rows?.length">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
              No accounts found.
            </td>
          </tr>

          <!-- Rows -->
          <tr
            v-else
            v-for="row in rows"
            :key="row.id"
            class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-900/40"
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
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="{
                  'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200': row.type === 'bank',
                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200': row.type === 'mobile',
                  'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/40 dark:text-fuchsia-200': row.type === 'card',
                  'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': !['bank', 'mobile', 'card'].includes(String(row.type ?? '')),
                }"
              >
                {{ row.type ?? '—' }}
              </span>
            </td>
            <td class="px-4 py-3 tabular-nums">{{ row.account_number ?? '—' }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                :class="row.is_active
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                  : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'"
              >
                <span class="inline-block h-1.5 w-1.5 rounded-full"
                      :class="row.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                {{ row.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap">{{ fmtDate(row.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <button
                  v-if="canView"
                  class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                  @click="$emit('view', row)"
                >
                  View
                </button>
                <button
                  v-if="canEdit"
                  class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                  @click="$emit('edit', row)"
                >
                  Edit
                </button>
                <button
                  v-if="canDelete"
                  class="rounded-lg border px-2 py-1 text-xs text-red-600 border-red-300 hover:bg-red-50 dark:text-red-300 dark:border-red-700 dark:hover:bg-red-900/30"
                  @click="$emit('delete', row)"
                >
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination?.links?.length" class="mt-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Showing {{ pagination.from ?? 0 }}–{{ pagination.to ?? rows.length }} of {{ pagination.total ?? rows.length }}
      </p>
      <nav class="flex items-center gap-1">
        <button
          v-for="(link, i) in pagination.links"
          :key="i"
          class="min-w-8 rounded-md px-2 py-1 text-sm"
          :class="[
            link.active
              ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900'
              : 'border border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800'
          ]"
          :disabled="!link.url"
          @click="onPage(link)"
          v-html="link.label"
        />
      </nav>
    </div>
  </div>
</template>
