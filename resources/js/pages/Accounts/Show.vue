<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

type Account = {
  id: number | string
  code: string
  name: string
  currency?: string | null

  account_name?: string | null
  is_active?: boolean | null
  type?: 'bank' | 'card' | 'mobile' | string | null

  bank_name?: string | null
  bank_routing_number?: string | null
  swift_code?: string | null
  bank_iban?: string | null
  bank_address?: string | null

  account_number?: string | null

  opening_balance?: number | null
  current_balance?: number | null

  card_type?: string | null
  card_valid_from?: string | null   // YYYY-MM-DD
  card_expiry?: string | null       // YYYY-MM-DD
  card_cvv?: string | null
  card_pin?: string | null

  description?: string | null
  created_at?: string | null
  updated_at?: string | null
}

const props = defineProps<{ account: Account }>()

const showCvv = ref(false)
const showPin = ref(false)

const breadcrumbs = [
  { title: 'Accounts', href: route('accounts.index') },
  { title: props.account.name ?? `#${props.account.id}` },
]

function fmtDate(iso?: string | null) {
  if (!iso) return '—'
  const d = new Date(iso)
  return Number.isNaN(d.getTime())
    ? iso
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

function destroy() {
  if (!confirm('Delete this account?')) return
  router.delete(route('accounts.destroy', props.account.id))
}

/** Raw number followed by currency; keeps currency visible even if amount is empty */
function rawAmount(amount: unknown): string {
  const a = amount === 0 ? 0 : (amount as number | null | undefined)
  const val = a === null || a === undefined || a === ('' as any) ? '—' : String(a)
  const cur = props.account.currency ?? ''
  return cur ? `${val} ${cur}` : val
}

const statusLabel = computed(() => (props.account.is_active ? 'Active' : 'Inactive'))
const typeLabel = computed(() => (props.account.type ? String(props.account.type) : '—'))
</script>

<template>
  <Head :title="`Account • ${props.account.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
      <!-- Header + actions -->
      <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0">
          <h1 class="truncate text-2xl font-semibold">
            {{ props.account.name }}
          </h1>

          <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-gray-500 dark:text-gray-400">
              Code: <span class="font-mono">{{ props.account.code }}</span>
            </span>

            <span
              v-if="props.account.currency"
              class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
            >
              Currency: <span class="ml-1 font-semibold">{{ props.account.currency }}</span>
            </span>

            <!-- status -->
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
              :class="
                props.account.is_active
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200'
                  : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200'
              "
            >
              {{ statusLabel }}
            </span>

            <!-- type -->
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
              :class="{
                'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200': props.account.type === 'bank',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200': props.account.type === 'mobile',
                'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/40 dark:text-fuchsia-200': props.account.type === 'card',
                'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200': !['bank', 'mobile', 'card'].includes(
                  String(props.account.type ?? '')
                ),
              }"
            >
              {{ typeLabel }}
            </span>

            <!-- show card brand chip whenever present -->
            <span
              v-if="props.account.card_type"
              class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium dark:bg-gray-800"
            >
              Card: <span class="ml-1 capitalize">{{ props.account.card_type }}</span>
            </span>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <Link
            :href="route('accounts.index')"
            as="button"
            class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
          >
            ← Back
          </Link>
          <Link
            :href="route('accounts.edit', props.account.id)"
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
        <!-- Account info -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Account</h2>
          <dl class="grid grid-cols-3 gap-3 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Account Name</dt>
            <dd class="col-span-2">{{ props.account.account_name ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Account No.</dt>
            <dd class="col-span-2 font-mono">{{ props.account.account_number ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Currency</dt>
            <dd class="col-span-2">{{ props.account.currency ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Opening Balance</dt>
            <dd class="col-span-2 font-mono">{{ rawAmount(props.account.opening_balance) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Current Balance</dt>
            <dd class="col-span-2 font-mono">{{ rawAmount(props.account.current_balance) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Description</dt>
            <dd class="col-span-2">{{ props.account.description ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="col-span-2">{{ fmtDate(props.account.created_at) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Updated</dt>
            <dd class="col-span-2">{{ fmtDate(props.account.updated_at) }}</dd>
          </dl>
        </section>

        <!-- Bank / identifiers -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Bank & Identifiers</h2>
          <dl class="grid grid-cols-3 gap-3 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Bank Name</dt>
            <dd class="col-span-2">{{ props.account.bank_name ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Routing No.</dt>
            <dd class="col-span-2 font-mono">{{ props.account.bank_routing_number ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">SWIFT</dt>
            <dd class="col-span-2 font-mono">{{ props.account.swift_code ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">IBAN</dt>
            <dd class="col-span-2 font-mono">{{ props.account.bank_iban ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Bank Address</dt>
            <dd class="col-span-2">{{ props.account.bank_address ?? '—' }}</dd>
          </dl>
        </section>

        <!-- Card details (ALWAYS visible) -->
        <section class="rounded-xl border border-gray-200 p-4 md:col-span-2 dark:border-gray-800">
          <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Card</h2>
          <dl class="grid grid-cols-3 gap-3 text-sm">
            <dt class="text-gray-500 dark:text-gray-400">Brand</dt>
            <dd class="col-span-2 capitalize">{{ props.account.card_type ?? '—' }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Valid From</dt>
            <dd class="col-span-2">{{ fmtDate(props.account.card_valid_from) }}</dd>

            <dt class="text-gray-500 dark:text-gray-400">Expiry</dt>
            <dd class="col-span-2">{{ fmtDate(props.account.card_expiry) }}</dd>

            <!-- CVV with eye toggle -->
            <dt class="text-gray-500 dark:text-gray-400">CVV</dt>
            <dd class="col-span-2 flex items-center gap-2 font-mono">
              <span>{{ showCvv ? (props.account.card_cvv ?? '—') : props.account.card_cvv ? '•••' : '—' }}</span>
              <button
                type="button"
                class="rounded-md border px-1.5 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                :title="showCvv ? 'Hide CVV' : 'Show CVV'"
                @click="showCvv = !showCvv"
              >
                <svg
                  v-if="!showCvv"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z" />
                  <circle cx="12" cy="12" r="3" stroke-width="1.5" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3l18 18" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M2.25 12C3.75 7.5 7.5 5 12 5c2.2 0 4.17.62 5.83 1.67M20.25 12c-.6 1.8-1.63 3.32-3.03 4.45A10.6 10.6 0 0 1 12 19c-4.5 0-8.25-2.5-9.75-7" />
                </svg>
              </button>
            </dd>

            <!-- PIN with eye toggle -->
            <dt class="text-gray-500 dark:text-gray-400">PIN</dt>
            <dd class="col-span-2 flex items-center gap-2 font-mono">
              <span>{{ showPin ? (props.account.card_pin ?? '—') : props.account.card_pin ? '••••' : '—' }}</span>
              <button
                type="button"
                class="rounded-md border px-1.5 py-1 text-xs hover:bg-gray-50 dark:hover:bg-gray-800"
                :title="showPin ? 'Hide PIN' : 'Show PIN'"
                @click="showPin = !showPin"
              >
                <svg
                  v-if="!showPin"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M2.036 12.322a1 1 0 0 1 0-.644C3.423 7.51 7.36 5 12 5s8.577 2.51 9.964 6.678a1 1 0 0 1 0 .644C20.577 16.49 16.64 19 12 19s-8.577-2.51-9.964-6.678z" />
                  <circle cx="12" cy="12" r="3" stroke-width="1.5" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3l18 18" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M2.25 12C3.75 7.5 7.5 5 12 5c2.2 0 4.17.62 5.83 1.67M20.25 12c-.6 1.8-1.63 3.32-3.03 4.45A10.6 10.6 0 0 1 12 19c-4.5 0-8.25-2.5-9.75-7" />
                </svg>
              </button>
            </dd>
          </dl>

          <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
            For security, avoid storing or displaying CVV/PIN in production environments.
          </p>
        </section>
      </div>

      <!-- At a glance -->
      <div class="mt-6 grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
          <div class="text-gray-500 dark:text-gray-400">Opening Balance</div>
          <div class="font-mono text-base">{{ rawAmount(props.account.opening_balance) }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
          <div class="text-gray-500 dark:text-gray-400">Current Balance</div>
          <div class="font-mono text-base">{{ rawAmount(props.account.current_balance) }}</div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
