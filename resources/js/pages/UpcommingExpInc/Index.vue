<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import VueSelect from "vue3-select-component";
import DataTable from 'datatables.net-vue3'
import DataTablesLib from 'datatables.net';
import DataTablesCore from 'datatables.net';

DataTable.use(DataTablesCore);
DataTable.use(DataTablesLib);

type UpItem = {
  id: number | string
  title: string
  description?: string | null
  eia_id?: number | string | null
  date?: string | null
  type?: 'income' | 'expense' | string | null
  attachments?: string[] | null
  created_at?: string | null
  amount?: string | number | null
  currency?: string | null
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type Paginator<T> = {
  data: T[]
  current_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  links: PaginationLink[]
}

type Option = { id: number | string; name: string }

const props = defineProps<{
  upcommingExpIncs: Paginator<UpItem>
  filters: {
    search?: string | null
    type?: 'income' | 'expense' | '' | null
    eia_id?: number | string | '' | null
    date_from?: string | null
    date_to?: string | null
  }
  accounts: Option[]
}>()

const breadcrumbs = [{ title: 'Upcoming E/I', href: route('upcomming-expense-income.index') }]

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return isNaN(d.getTime())
    ? iso
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

// currency-aware display like "BDT 1,234.50"
function fmtMoney(amount?: string | number | null, currency?: string | null) {
  if (amount === null || amount === undefined || amount === '') return '—'
  const n = Number(amount)
  if (Number.isNaN(n)) return String(amount)
  const code = (currency ?? 'BDT').toUpperCase()
  const formatted = n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  return `${code}&nbsp;${formatted}`
}

function destroy(row: { id: number | string; title?: string }) {
  if (!confirm(`Delete item${row.title ? ` “${row.title}”` : ''}?`)) return
  router.delete(route('upcomming-expense-income.destroy', row.id), {
    preserveState: true,
    preserveScroll: true,
  })
}

/** ---------------- Filters (local reactive copy) ---------------- */
const f = reactive({
  search: props.filters?.search ?? '',
  type: (props.filters?.type as '' | 'income' | 'expense' | null) ?? '',
  eia_id: (props.filters?.eia_id as string | number | '' | null) ?? '',
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
})

function cleanQuery(obj: Record<string, any>) {
  return Object.fromEntries(Object.entries(obj).filter(([_, v]) => v !== '' && v !== null && v !== undefined))
}

function applyFilters() {
  router.get(route('upcomming-expense-income.index'), cleanQuery(f), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  Object.assign(f, {
    search: '',
    type: '',
    eia_id: '',
    date_from: '',
    date_to: '',
  })
  applyFilters()
}

const activeFilterCount = computed(() => Object.keys(cleanQuery(f)).length)

/** Row index start for pagination-aware # column */
const rowStart = computed(() => Number(props.upcommingExpIncs.from ?? 1))

/** Lookup map for linked account names */
const accountNameById = computed<Record<string, string>>(() => {
  const map: Record<string, string> = {}
  for (const a of props.accounts) map[String(a.id)] = a.name
  return map
})
function linkedName(eia_id?: number | string | null) {
  if (eia_id === null || eia_id === undefined || eia_id === '') return '—'
  return accountNameById.value[String(eia_id)] ?? String(eia_id)
}
</script>

<template>
  <Head title="Upcoming E/I" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Filters -->
      <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold tracking-wide text-gray-600 uppercase dark:text-gray-300">
            Filters
            <span v-if="activeFilterCount" class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">
              {{ activeFilterCount }}
            </span>
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

        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <!-- Title search -->
          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Title / Description</label>
            <input
              v-model="f.search"
              type="text"
              placeholder="Search..."
              class="w-full rounded-lg border px-3 py-2 dark:border-gray-700"
            />
          </div>

          <!-- Type -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Type</label>
            <VueSelect v-model="f.type" class="w-full"
                :options="[
                  {label: '— Any —', value: ''},
                  {label: 'Income', value: 'income'},
                  {label: 'Expense', value: 'expense'}
                ]"
            />
          </div>

          <!-- Linked Account -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Linked Account</label>
            <VueSelect v-model="f.eia_id" class="w-full"
                :options="[{label: '— Any —', value: ''}, ...props.accounts.map(a => ({label: a.name, value: a.id}))]"
            />
          </div>

          <!-- Date From -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">From</label>
            <input v-model="f.date_from" type="date" class="w-full rounded-lg border px-3 py-2 dark:border-gray-700" />
          </div>

          <!-- Date To -->
          <div>
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">To</label>
            <input v-model="f.date_to" type="date" class="w-full rounded-lg border px-3 py-2 dark:border-gray-700" />
          </div>
        </div>
      </section>

      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
        <div class="h-full w-full p-4">
          <!-- Toolbar -->
          <div class="mb-4 flex items-center justify-end">
            <Link
              :href="route('upcomming-expense-income.create')"
              as="button"
              class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow hover:opacity-90 dark:bg-white dark:text-gray-900"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14M5 12h14" />
              </svg>
              New Upcoming
            </Link>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <DataTable class="min-w-full text-left text-sm" :options="{ paging: false, searching: false, info: false }">
              <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
                <tr>
                  <th class="w-14 px-4 py-3">#</th>
                  <th class="px-4 py-3">Title</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3">Type</th>
                  <th class="px-4 py-3 text-right">Amount</th>
                  <th class="px-4 py-3">Linked A/C</th>
                  <th class="px-4 py-3">Attachments</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>

              <tbody>
                <!-- <tr v-if="!props.upcommingExpIncs?.data?.length">
                  <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No upcoming items found.</td>
                </tr> -->

                <tr
                  v-for="(row, i) in props.upcommingExpIncs.data"
                  :key="row.id"
                  class="border-t border-gray-100 hover:bg-gray-50/60 dark:border-gray-800 dark:hover:bg-gray-900/40"
                >
                  <!-- Index -->
                  <td class="px-4 py-3 tabular-nums text-gray-500">
                    {{ rowStart + i }}
                  </td>

                  <td class="px-4 py-3 font-medium">
                    <div class="flex flex-col">
                      <span class="truncate">{{ row.title }}</span>
                      <span v-if="row.description" class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ row.description }}
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">{{ fmtDate(row.date) }}</td>

                  <td class="px-4 py-3">
                    <span
                      class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize"
                      :class="
                        row.type === 'income'
                          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                          : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                      "
                    >
                      {{ row.type ?? '—' }}
                    </span>
                  </td>

                  <td class="px-4 py-3 text-right tabular-nums" v-html="fmtMoney(row.amount, row.currency)"></td>
                  <td class="px-4 py-3">{{ linkedName(row.eia_id) }}</td>

                  <td class="px-4 py-3">
                    <span class="text-xs text-gray-600 dark:text-gray-300">
                      {{ Array.isArray(row.attachments) ? row.attachments.length : 0 }}
                    </span>
                  </td>

                  <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                      <!-- Show -->
                      <Link
                        :href="route('upcomming-expense-income.show', row.id)"
                        as="button"
                        class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        aria-label="View"
                        :preserve-state="true"
                        :preserve-scroll="true"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z" />
                          <circle cx="12" cy="12" r="3" stroke-width="1.5" />
                        </svg>
                      </Link>

                      <!-- Edit -->
                      <Link
                        :href="route('upcomming-expense-income.edit', row.id)"
                        as="button"
                        class="rounded-lg border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                        aria-label="Edit"
                        :preserve-state="true"
                        :preserve-scroll="true"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M16.862 3.487a2.25 2.25 0 0 1 3.182 3.182L8.25 18.463l-4.5 1.05 1.05-4.5L16.862 3.487z" />
                        </svg>
                      </Link>

                      <!-- Delete -->
                      <button
                        type="button"
                        class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
                        aria-label="Delete"
                        @click="destroy(row)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M3 6h18M9 6V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V6m-8 0l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13M10 11v6m4-6v6" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </DataTable>
          </div>

          <!-- Pagination -->
          <div v-if="props.upcommingExpIncs?.links?.length" class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Showing {{ props.upcommingExpIncs.from ?? 0 }}–{{ props.upcommingExpIncs.to ?? props.upcommingExpIncs.data.length }} of
              {{ props.upcommingExpIncs.total }}
            </p>
            <nav class="flex items-center gap-1">
              <Link
                v-for="(link, i) in props.upcommingExpIncs.links"
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
