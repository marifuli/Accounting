<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

type Category = { id: number | string; name: string }
type ExpenseIncomeAccount = {
  id: number | string
  name: string
  description?: string | null
  type?: 'income' | 'expense' | string | null
  transaction_category_id?: number | string | null
  // If you eager-load a relation, this will render automatically:
  transaction_category?: Category | null
  created_at?: string | null
  updated_at?: string | null
}

const props = defineProps<{ expense_income_account: ExpenseIncomeAccount }>()

const breadcrumbs = [
  { title: 'Expense/Income Accounts', href: route('expense-income-accounts.index') },
  { title: props.expense_income_account.name ?? `#${props.expense_income_account.id}` },
]

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return isNaN(d.getTime())
    ? iso
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

function destroyItem() {
  if (!confirm('Delete this item?')) return
  router.delete(route('expense-income-accounts.destroy', props.expense_income_account.id))
}
</script>

<template>
  <Head :title="`View • ${props.expense_income_account.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
      <!-- Header + actions -->
      <div class="mb-6 flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold">
            {{ props.expense_income_account.name }}
          </h1>
          <div class="mt-2">
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
              :class="props.expense_income_account.type === 'income'
                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200'"
            >
              {{ props.expense_income_account.type ?? '—' }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Link
            :href="route('expense-income-accounts.index')"
            as="button"
            class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
          >
            ← Back
          </Link>
          <Link
            :href="route('expense-income-accounts.edit', props.expense_income_account.id)"
            as="button"
            class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
          >
            Edit
          </Link>
          <button
            type="button"
            class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
            @click="destroyItem"
          >
            Delete
          </button>
        </div>
      </div>

      <!-- Details -->
      <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Details</h2>
        <dl class="grid grid-cols-3 gap-3 text-sm">
          <dt class="text-gray-500 dark:text-gray-400">Name</dt>
          <dd class="col-span-2">{{ props.expense_income_account.name }}</dd>

          <dt class="text-gray-500 dark:text-gray-400">Type</dt>
          <dd class="col-span-2 capitalize">{{ props.expense_income_account.type ?? '—' }}</dd>

          <dt class="text-gray-500 dark:text-gray-400">Parent Category</dt>
          <dd class="col-span-2">
            <!-- If relation is eager-loaded -->
            <template v-if="props.expense_income_account.transaction_category">
              {{ props.expense_income_account.transaction_category.name }}
              <span class="text-xs text-gray-500">(#{{ props.expense_income_account.transaction_category.id }})</span>
            </template>
            <!-- Otherwise fall back to ID -->
            <template v-else>
              {{ props.expense_income_account.transaction_category_id ?? '—' }}
            </template>
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">Description</dt>
          <dd class="col-span-2">
            <span class="whitespace-pre-line">{{ props.expense_income_account.description ?? '—' }}</span>
          </dd>

          <dt class="text-gray-500 dark:text-gray-400">Created</dt>
          <dd class="col-span-2">{{ fmtDate(props.expense_income_account.created_at) }}</dd>

          <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
          <dd class="col-span-2">{{ fmtDate(props.expense_income_account.updated_at) }}</dd>
        </dl>
      </section>
    </div>
  </AppLayout>
</template>
