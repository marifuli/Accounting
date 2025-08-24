<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

type Option = { id: number | string; name: string }

// 👇 must match controller: compact('transactionCategories')
const props = defineProps<{
  transactionCategories: Option[]
}>()

const breadcrumbs = [
  { title: 'Expense/Income Accounts', href: route('expense-income-accounts.index') },
  { title: 'Create' },
]

const form = useForm({
  name: '',
  description: '' as string | null,
  type: 'expense' as 'income' | 'expense',
  transaction_category_id: null as number | string | null,
})

function submit() {
  form.post(route('expense-income-accounts.store'), {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Create Expense/Income Account" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-3xl p-4 sm:p-6 lg:p-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">New Expense/Income Account</h1>
        <div class="flex gap-2">
          <Link
            :href="route('expense-income-accounts.index')"
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
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <div class="grid gap-4">
            <div>
              <label class="mb-1 block text-sm font-medium">Name <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                placeholder="e.g. Groceries"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Description</label>
              <textarea
                v-model="form.description"
                rows="4"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
                placeholder="Optional details..."
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Type <span class="text-red-500">*</span></label>
              <select
                v-model="form.type"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.type ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
              >
                <option value="expense">Expense</option>
                <option value="income">Income</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Parent Category</label>
              <select
                v-model="form.transaction_category_id"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              >
                <option :value="null">— None —</option>
                <!-- 👇 use transactionCategories from controller -->
                <option v-for="opt in props.transactionCategories" :key="opt.id" :value="opt.id">
                  {{ opt.name }}
                </option>
              </select>
              <p v-if="form.errors.transaction_category_id" class="mt-1 text-xs text-red-600">
                {{ form.errors.transaction_category_id }}
              </p>
            </div>
          </div>
        </section>
      </form>
    </div>
  </AppLayout>
</template>
