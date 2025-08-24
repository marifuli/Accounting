<!-- resources/js/Pages/UpcommingExpInc/Edit.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

type UpItem = {
  id: number | string
  title: string
  description?: string | null
  eia_id?: number | string | null
  date?: string | null
  type?: 'income' | 'expense' | string | null
  attachments?: string[] | null
}

type EIA = { id: number | string; name: string }

// Props keys must match controller
const props = defineProps<{
  upcomming_expense_income: UpItem
  expIncAccounts: EIA[]
}>()

const breadcrumbs = [
  { title: 'Upcoming E/I', href: route('upcomming-expense-income.index') },
  { title: 'Edit' },
]

const form = useForm({
  title: props.upcomming_expense_income.title ?? '',
  description: (props.upcomming_expense_income.description ?? '') as string | null,
  eia_id: (props.upcomming_expense_income.eia_id ?? null) as number | string | null,
  date: props.upcomming_expense_income.date ?? '',
  type: (props.upcomming_expense_income.type ?? 'expense') as 'income' | 'expense',

  // new files only; existing stay as-is unless backend lets you remove them
  attachments: [] as File[],
})

function onFilesChanged(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files) return
  form.attachments = Array.from(input.files)
}

function removeNewFile(idx: number) {
  form.attachments.splice(idx, 1)
}

function submit() {
  // Send FormData and spoof PUT
  form
    .transform((data) => {
      const fd = new FormData()
      fd.append('title', data.title ?? '')
      fd.append('description', data.description ?? '')
      fd.append('eia_id', (data.eia_id ?? '').toString())
      fd.append('date', data.date ?? '')
      fd.append('type', data.type)

      ;(data.attachments as File[]).forEach((file) => {
        fd.append('attachments[]', file)
      })

      fd.append('_method', 'put')
      return fd
    })
    .post(route('upcomming-expense-income.update', props.upcomming_expense_income.id), {
      preserveScroll: true,
      onFinish: () => form.reset('attachments'),
    })
}
</script>

<template>
  <Head :title="`Edit • ${props.upcomming_expense_income.title}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Upcoming Income/Expense</h1>
        <div class="flex gap-2">
          <Link
            :href="route('upcomming-expense-income.index')"
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
            Save Changes
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="grid gap-6">
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Details</h2>
          <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">Title <span class="text-red-500">*</span></label>
              <input
                v-model="form.title"
                type="text"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.title ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
              />
              <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Date <span class="text-red-500">*</span></label>
              <input
                v-model="form.date"
                type="date"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.date ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
              />
              <p v-if="form.errors.date" class="mt-1 text-xs text-red-600">{{ form.errors.date }}</p>
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

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">Linked Account (optional)</label>
              <select
                v-model="form.eia_id"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.eia_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
              >
                <option :value="null">— None —</option>
                <option v-for="acc in props.expIncAccounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
              </select>
              <p v-if="form.errors.eia_id" class="mt-1 text-xs text-red-600">{{ form.errors.eia_id }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">Description</label>
              <textarea
                v-model="form.description"
                rows="4"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>
          </div>
        </section>

        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Add Attachments</h2>
          <div class="grid gap-3">
            <input
              type="file"
              multiple
              @change="onFilesChanged"
              class="block w-full cursor-pointer rounded-lg border border-dashed border-gray-300 p-3 text-sm dark:border-gray-700"
            />
            <ul v-if="form.attachments.length" class="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800">
              <li
                v-for="(f, idx) in form.attachments"
                :key="`${f.name}-${idx}`"
                class="flex items-center justify-between px-3 py-2 text-sm"
              >
                <span class="truncate">{{ f.name }}</span>
                <button
                  type="button"
                  class="rounded-md border px-2 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                  @click="removeNewFile(idx)"
                >
                  Remove
                </button>
              </li>
            </ul>

            <p v-if="form.errors['attachments']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments'] }}</p>
            <p v-if="form.errors['attachments.*']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.*'] }}</p>

            <p class="text-xs text-gray-500 dark:text-gray-400">
              Existing files remain attached. New uploads will be added.
            </p>
          </div>
        </section>
      </form>
    </div>
  </AppLayout>
</template>
