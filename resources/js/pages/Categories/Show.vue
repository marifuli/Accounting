<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { formatDate } from '@/lib/utils'

type TransactionCategory = {
  id: number | string
  name: string
  description?: string | null
  is_active?: boolean | null
  created_at?: string | null
  updated_at?: string | null
}

// 👇 matches compact('transaction_category') from your controller
const props = defineProps<{ transaction_category: TransactionCategory }>()

const breadcrumbs = [
  { title: 'Transaction Categories', href: route('transaction-categories.index') },
  { title: props.transaction_category.name || 'Details' },
]

function destroy() {
  if (!confirm(`Delete category “${props.transaction_category.name}”?`)) return
  router.delete(route('transaction-categories.destroy', props.transaction_category.id), {
    preserveScroll: true,
  })
}

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  return formatDate((iso))
}
</script>

<template>
  <Head :title="`Category • ${props.transaction_category.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">
          {{ props.transaction_category.name }}
        </h1>
        <div class="flex items-center gap-2">
          <Link
            :href="route('transaction-categories.edit', props.transaction_category.id)"
            as="button"
            class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
          >
            Edit
          </Link>
          <button
            type="button"
            class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30"
            @click="destroy"
          >
            Delete
          </button>
          <Link
            :href="route('transaction-categories.index')"
            as="button"
            class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
          >
            Back
          </Link>
        </div>
      </div>

      <!-- Details card -->
      <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-800">
        <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</dt>
            <dd class="mt-1 text-sm">{{ props.transaction_category.name }}</dd>
          </div>

          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1">
              <span
                class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="
                  props.transaction_category.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
                "
              >
                <span
                  class="inline-block h-1.5 w-1.5 rounded-full"
                  :class="props.transaction_category.is_active ? 'bg-green-500' : 'bg-red-500'"
                />
                {{ props.transaction_category.is_active ? 'Active' : 'Inactive' }}
              </span>
            </dd>
          </div>

          <div class="md:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</dt>
            <dd class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
              {{ props.transaction_category.description ?? '—' }}
            </dd>
          </div>

          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="mt-1 text-sm">{{ fmtDate(props.transaction_category.created_at) }}</dd>
          </div>

          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Updated</dt>
            <dd class="mt-1 text-sm">{{ fmtDate(props.transaction_category.updated_at) }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </AppLayout>
</template>
