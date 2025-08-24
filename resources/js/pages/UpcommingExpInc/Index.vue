<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

type UpItem = {
  id: number | string
  title: string
  description?: string | null
  eia_id?: number | string | null
  date?: string | null
  type?: 'income' | 'expense' | string | null
  attachments?: string[] | null
  created_at?: string | null
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

// 👇 MUST match controller compact('upcommingExpIncs')
const props = defineProps<{ upcommingExpIncs: Paginator<UpItem> }>()

const breadcrumbs = [{ title: 'Upcoming E/I', href: route('upcomming-expense-income.index') }]

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return isNaN(d.getTime())
    ? iso
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

function destroy(row: { id: number | string; title?: string }) {
  if (!confirm(`Delete item${row.title ? ` “${row.title}”` : ''}?`)) return
  router.delete(route('upcomming-expense-income.destroy', row.id), {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Upcoming E/I" />

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
            <table class="min-w-full text-left text-sm">
              <thead class="bg-gray-50 text-gray-700 dark:bg-gray-900/60 dark:text-gray-200">
                <tr>
                  <th class="px-4 py-3">Title</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3">Type</th>
                  <th class="px-4 py-3">Linked A/C</th>
                  <th class="px-4 py-3">Attachments</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>

              <tbody>
                <tr v-if="!props.upcommingExpIncs?.data?.length">
                  <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No upcoming items found.</td>
                </tr>

                <tr
                  v-for="row in props.upcommingExpIncs.data"
                  :key="row.id"
                  class="border-t border-gray-100 hover:bg-gray-50/60 dark:border-gray-800 dark:hover:bg-gray-900/40"
                >
                  <td class="px-4 py-3 font-medium">
                    <div class="flex flex-col">
                      <span class="truncate">{{ row.title }}</span>
                      <span v-if="row.description" class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ row.description }}
                      </span>
                    </div>
                  </td>

                  <td class="px-4 py-3 whitespace-nowrap">
                    {{ fmtDate(row.date) }}
                  </td>

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

                  <td class="px-4 py-3">
                    {{ row.eia_id ?? '—' }}
                  </td>

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
            </table>
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
