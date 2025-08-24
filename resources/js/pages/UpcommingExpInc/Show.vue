<!-- resources/js/Pages/UpcommingExpInc/Show.vue -->
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
  updated_at?: string | null
}

// 👇 must match controller prop key
const props = defineProps<{ upcomming_expense_income: UpItem }>()

const breadcrumbs = [
  { title: 'Upcoming E/I', href: route('upcomming-expense-income.index') },
  { title: props.upcomming_expense_income.title },
]

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return isNaN(d.getTime())
    ? iso
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

function fileName(p: string) {
  try { return p.split('/').pop() || p } catch { return p }
}

function destroy() {
  if (!confirm('Delete this upcoming item?')) return
  router.delete(route('upcomming-expense-income.destroy', props.upcomming_expense_income.id))
}
</script>

<template>
  <Head :title="`Upcoming • ${props.upcomming_expense_income.title}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
      <!-- Header + actions -->
      <div class="mb-6 flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold">{{ props.upcomming_expense_income.title }}</h1>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-600 dark:text-gray-300">Date: {{ fmtDate(props.upcomming_expense_income.date) }}</span>
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
              :class="
                props.upcomming_expense_income.type === 'income'
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                  : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
              "
            >
              {{ props.upcomming_expense_income.type }}
          </span>
          <span class="text-gray-600 dark:text-gray-300">Linked A/C: {{ props.upcomming_expense_income.eia_id ?? '—' }}</span>
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
            :href="route('upcomming-expense-income.edit', props.upcomming_expense_income.id)"
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

      <!-- Details -->
      <div class="grid gap-6 md:grid-cols-2">
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Details</h2>
          <dl class="grid grid-cols-3 gap-3 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Title</dt>
            <dd class="col-span-2">{{ props.upcomming_expense_income.title }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Date</dt>
            <dd class="col-span-2">{{ fmtDate(props.upcomming_expense_income.date) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Type</dt>
            <dd class="col-span-2 capitalize">{{ props.upcomming_expense_income.type }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Linked Account</dt>
            <dd class="col-span-2">{{ props.upcomming_expense_income.eia_id ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Description</dt>
            <dd class="col-span-2 whitespace-pre-wrap">{{ props.upcomming_expense_income.description ?? '—' }}</dd>
          </dl>
        </section>

        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Attachments</h2>
          <div v-if="props.upcomming_expense_income.attachments?.length" class="space-y-2">
            <div
              v-for="(p, i) in props.upcomming_expense_income.attachments"
              :key="`${p}-${i}`"
              class="flex items-center justify-between rounded-md border px-3 py-2 text-sm dark:border-gray-800"
            >
              <span class="truncate">{{ fileName(p) }}</span>
              <a :href="`/storage/${p}`" target="_blank" class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
                Open
              </a>
            </div>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No attachments.</p>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
