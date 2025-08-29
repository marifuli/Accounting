<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'

// ---- Types
type Account = {
  id: number | string
  name: string
  code?: string | null
  type?: 'bank' | 'card' | 'mobile' | string | null
  account_number?: string | null
  is_active?: boolean | null
  created_at?: string | null
  // optionally present if your API includes these:
  current_balance?: string | number | null
  currency?: string | null
  bank_name?: string | null
}

type PaginationLink = { url: string | null; label: string; active: boolean }
type AccountsPaginator = {
  data: Account[]
  current_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
  links: PaginationLink[]
}

// ---- Props
const props = defineProps<{ accounts: AccountsPaginator }>()

// ---- Breadcrumbs
const breadcrumbs = [{ title: 'Accounts', href: '/accounts' }]

// ---- Flash (success/error)
type FlashProps = { success?: string; error?: string }
const page = usePage()
const flash = computed<FlashProps>(() => (page.props.flash ?? {}) as FlashProps)

// ---- Helpers
function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return isNaN(d.getTime()) ? iso : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

function visit(link: PaginationLink) {
  if (link.url) router.visit(link.url, { preserveState: true, preserveScroll: true })
}

function destroy(row: { id: number | string; name?: string }) {
  if (!confirm(`Delete account${row.name ? ` “${row.name}”` : ''}?`)) return
  router.delete(`/accounts/${row.id}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

function fmtMoney(amount?: string | number | null, currency?: string | null) {
  if (amount === null || amount === undefined || amount === '') return '—'
  const n = Number(amount)
  if (Number.isNaN(n)) return String(amount)
  const code = (currency ?? 'BDT').toUpperCase()
  const formatted = n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
  return `${code} ${formatted}`
}
</script>

<template>
  <Head title="Accounts" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Hero placeholders -->
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
        <div class="h-full w-full p-4">
          <!-- Flash messages -->
          <div class="mb-3 space-y-2" aria-live="polite">
            <div
              v-if="flash.error"
              class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200"
            >
              {{ flash.error }}
            </div>
            <div
              v-if="flash.success"
              class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200"
            >
              {{ flash.success }}
            </div>
          </div>

          <!-- Toolbar -->
          <div class="mb-4 flex items-center justify-end">
            <Link
              :href="route('accounts.create')"
              as="button"
              class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white shadow hover:opacity-90 dark:bg-white dark:text-gray-900"
              :preserve-state="true"
              :preserve-scroll="true"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 5v14M5 12h14" />
              </svg>
              New Account
            </Link>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto rounded-xl">
            <table class="min-w-full text-left text-sm">
              <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
                <tr>
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Code</th>
                  <th class="px-4 py-3">Type</th>
                  <th class="px-4 py-3">Account No.</th>
                  <th class="px-4 py-3">Current Balance</th>
                  <th class="px-4 py-3">Active</th>
                  <th class="px-4 py-3">Created</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!props.accounts?.data?.length">
                  <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No accounts found.</td>
                </tr>

                <tr
                  v-for="row in props.accounts.data"
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
                  <td class="px-4 py-3 tabular-nums">
                    {{ fmtMoney((row as any).current_balance, (row as any).currency) }}
                  </td>
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
                      />
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z" />
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
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="props.accounts?.links?.length" class="mt-4 flex items-center justify-between gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
              Showing {{ props.accounts.from ?? 0 }}–{{ props.accounts.to ?? props.accounts.data.length }} of
              {{ props.accounts.total }}
            </p>
            <nav class="flex items-center gap-1">
              <Link
                v-for="(link, i) in props.accounts.links"
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
