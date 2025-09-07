<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import VueSelect from "vue3-select-component";
import { formatDate } from '@/lib/utils';
function fmtDate(iso?: string | null) {
  if (!iso) return '—';
  return formatDate((iso));
}

type Rel = { id: number | string; name: string };
type TransactionRow = {
  id: number | string;
  name: string;
  description?: string | null;
  category?: Rel | null;
  from_account?: Rel | null;
  to_account?: Rel | null;
  attachments?: string[] | null;
  send_total_amount?: number | string | null;
  send_actual_amount?: number | string | null;
  receive_total_amount?: number | string | null;
  receive_actual_amount?: number | string | null;
  created_at?: string | null;
  date?: string | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type Paginator<T> = {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  links: PaginationLink[];
};

type Option = { id: number | string; name: string };

const props = defineProps<{
  transactions: Paginator<TransactionRow>;
  filters: {
    name?: string | null; // NEW
    from_account_id?: string | number | null;
    to_account_id?: string | number | null;
    category_id?: string | number | null;

    send_total_amount_min?: string | number | null;
    send_total_amount_max?: string | number | null;
    send_actual_amount_min?: string | number | null;
    send_actual_amount_max?: string | number | null;
    receive_total_amount_min?: string | number | null;
    receive_total_amount_max?: string | number | null;
    receive_actual_amount_min?: string | number | null;
    receive_actual_amount_max?: string | number | null;

    from_date?: string | null;
    to_date?: string | null;
  };
  accounts: Option[];
  categories: Option[];
}>();

const breadcrumbs = [{ title: 'Transactions', href: route('transactions.index') }];

// Local reactive copy of filters so user edits don't immediately fetch
const f = reactive({
  name: props.filters?.name ?? '', // NEW
  from_account_id: props.filters?.from_account_id ?? '',
  to_account_id: props.filters?.to_account_id ?? '',
  category_id: props.filters?.category_id ?? '',

  send_total_amount_min: props.filters?.send_total_amount_min ?? '',
  send_total_amount_max: props.filters?.send_total_amount_max ?? '',
  send_actual_amount_min: props.filters?.send_actual_amount_min ?? '',
  send_actual_amount_max: props.filters?.send_actual_amount_max ?? '',
  receive_total_amount_min: props.filters?.receive_total_amount_min ?? '',
  receive_total_amount_max: props.filters?.receive_total_amount_max ?? '',
  receive_actual_amount_min: props.filters?.receive_actual_amount_min ?? '',
  receive_actual_amount_max: props.filters?.receive_actual_amount_max ?? '',

    from_date: props.filters?.from_date ?? '',
    to_date: props.filters?.to_date ?? '',
});

function cleanQuery(obj: Record<string, any>) {
  // Remove empty values so the URL stays tidy
  return Object.fromEntries(
    Object.entries(obj).filter(([_, v]) => v !== '' && v !== null && v !== undefined)
  );
}

function applyFilters() {
  router.get(route('transactions.index'), cleanQuery(f), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function resetFilters() {
  Object.assign(f, {
    name: '',
    from_account_id: '',
    to_account_id: '',
    category_id: '',

    send_total_amount_min: '',
    send_total_amount_max: '',
    send_actual_amount_min: '',
    send_actual_amount_max: '',
    receive_total_amount_min: '',
    receive_total_amount_max: '',
    receive_actual_amount_min: '',
    receive_actual_amount_max: '',
  });
  applyFilters();
}

function fmtMoney(v?: number | string | null) {
  if (v === null || v === undefined || v === '') return '—';
  const n = Number(v);
  return isFinite(n)
    ? n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    : String(v);
}

function destroy(row: { id: number | string; name?: string }) {
  if (!confirm(`Delete transaction${row.name ? ` “${row.name}”` : ''}?`)) return;
  router.delete(route('transactions.destroy', row.id), {
    preserveState: true,
    preserveScroll: true,
  });
}

// For showing a compact summary of how many filters are active
const activeFilterCount = computed(() => Object.keys(cleanQuery(f)).length);

// Pagination-aware base index for serial column
const startIndex = computed(() => {
  const page = props.transactions?.current_page ?? 1;
  const per = props.transactions?.per_page ?? (props.transactions?.data?.length ?? 0);
  return (page - 1) * per;
});
</script>

<template>
  <Head title="Transactions" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Filters -->
      <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-300">
            Filters
            <span
              v-if="activeFilterCount"
              class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800"
              >{{ activeFilterCount }}</span
            >
          </h2>
          <div class="flex gap-2">
            <button class="rounded-lg border px-3 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-800" @click="resetFilters">
              Reset
            </button>
            <button
              class="rounded-lg bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:opacity-90 dark:bg-white dark:text-gray-900"
              @click="applyFilters"
            >
              Apply
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <!-- Name search (NEW) -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Name</label>
            <input
              v-model="f.name"
              type="text"
              placeholder="Search by name..."
              class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
            />
          </div>

          <!-- From Account -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From Account</label>
            <VueSelect v-model="f.from_account_id" class="w-full"
                :options="props.accounts.map(a => ({label: a.name, value: a.id}))"
            />
          </div>

          <!-- To Account -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To Account</label>
            <VueSelect v-model="f.to_account_id" class="w-full"
                :options="props.accounts.map(a => ({label: a.name, value: a.id}))"
            />
          </div>

          <!-- Category -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Category</label>
            <VueSelect v-model="f.category_id" class="w-full"
                :options="props.categories.map(c => ({label: c.name, value: c.id}))"
            />
          </div>

          <!-- Send Total Amount (min/max) -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Send Total (min)</label>
              <input
                v-model="f.send_total_amount_min"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Send Total (max)</label>
              <input
                v-model="f.send_total_amount_max"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
          </div>

          <!-- Send Actual Amount (min/max) -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Send Actual (min)</label>
              <input
                v-model="f.send_actual_amount_min"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Send Actual (max)</label>
              <input
                v-model="f.send_actual_amount_max"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
          </div>

          <!-- Receive Total Amount (min/max) -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Receive Total (min)</label>
              <input
                v-model="f.receive_total_amount_min"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Receive Total (max)</label>
              <input
                v-model="f.receive_total_amount_max"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
          </div>

          <!-- Receive Actual Amount (min/max) -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Receive Actual (min)</label>
              <input
                v-model="f.receive_actual_amount_min"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Receive Actual (max)</label>
              <input
                v-model="f.receive_actual_amount_max"
                type="number"
                step="0.01"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From Date</label>
              <input
                v-model="f.from_date"
                type="date"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To Date</label>
              <input
                v-model="f.to_date"
                type="date"
                inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Table + actions -->
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
        <div class="h-full w-full p-4">
          <!-- Toolbar -->
          <div class="mb-4 flex items-center justify-end">
            <Link
              :href="route('transactions.create')"
              as="button"
              class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow hover:opacity-90 dark:bg-white dark:text-gray-900"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14M5 12h14" />
              </svg>
              New Transaction
            </Link>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-left text-sm">
              <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
                <tr>
                  <th class="px-4 py-3 w-12">#</th> <!-- NEW serial column -->
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Category</th>
                  <th class="px-4 py-3">From</th>
                  <th class="px-4 py-3">Send (Actual/Total)</th>
                  <th class="px-4 py-3">Receive (Total/Actual)</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="!props.transactions?.data?.length">
                  <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    No transactions found.
                  </td>
                </tr>

                <tr
                  v-for="(row, i) in props.transactions.data"
                  :key="row.id"
                  class="border-t border-gray-100 hover:bg-gray-50/60 dark:border-gray-800 dark:hover:bg-gray-900/40"
                >
                  <td class="px-4 py-3 text-gray-500">{{ startIndex + i + 1 }}</td>

                  <td class="px-4 py-3 font-medium" >
                    <div style="width: 160px;">

                        {{ fmtDate(row.date || row.created_at) }}
                    </div>
                    </td>
                  <td class="px-4 py-3 font-medium">
                    <div class="" style="max-width: 240px;">
                      <span class="truncate">{{ row.name }}</span>
                      <span v-if="row.description" class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ row.description }}
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div style="width: 130px;">

                        {{ row.category?.name ?? '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3">
                    <div style="width: 250px;">
                    {{ row.from_account?.name ?? '—' }}
                    ->
                    {{ row.to_account?.name ?? '—' }}
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    <b>{{ fmtMoney(row.send_actual_amount) }}</b> / {{ fmtMoney(row.send_total_amount) }}
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ fmtMoney(row.receive_total_amount) }} / <b>{{ fmtMoney(row.receive_actual_amount) }}</b>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                      <Link
                        :href="route('transactions.show', row.id)"
                        as="button"
                        class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        aria-label="View"
                        :preserve-state="true"
                        :preserve-scroll="true"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor">
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z"
                          />
                          <circle cx="12" cy="12" r="3" stroke-width="1.5" />
                        </svg>
                      </Link>

                      <Link
                        :href="route('transactions.edit', row.id)"
                        as="button"
                        class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        aria-label="Edit"
                        :preserve-state="true"
                        :preserve-scroll="true"
                      >
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

                      <button
                        type="button"
                        class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                        aria-label="Delete"
                        @click="destroy(row)"
                      >
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
          <div v-if="props.transactions?.links?.length" class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Showing {{ props.transactions.from ?? 0 }}–{{ props.transactions.to ?? props.transactions.data.length }} of
              {{ props.transactions.total }}
            </p>
            <nav class="flex items-center gap-1">
              <Link
                v-for="(link, i) in props.transactions.links"
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
