<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

type AccountType = 'bank' | 'card' | 'mobile'

type Account = {
  id: number | string
  code: string
  swift_code?: string | null
  name: string
  account_name?: string | null

  currency: 'USD'|'EUR'|'GBP'|'JPY'|'AUD'|'CAD'|'CHF'|'CNY'|'INR'|'BRL'|'ZAR'|'BDT'|'other'
  opening_balance?: number | string
  current_balance?: number | string

  is_active?: boolean
  type: AccountType
  card_type?: string | null
  account_number?: string | null
  bank_name?: string | null
  bank_routing_number?: string | null
  card_valid_from?: string | null
  card_expiry?: string | null
  card_cvv?: string | null
  card_pin?: string | null
  bank_iban?: string | null
  bank_address?: string | null
  description?: string | null
}

const props = defineProps<{ account: Account }>()

const breadcrumbs = [
  { title: 'Accounts', href: route('accounts.index') },
  { title: 'Edit' },
]

// Card brands (from your migration)
const CARD_TYPES = [
  'visa','mastercard','amex','discover','unionpay','jcb','diners_club','maestro','visa_electron',
  'rupay','verve','troy','mir','elo','hipercard','bancontact','interac','dankort','bc_card',
  'mada','eftpos','cartes_bancaires','uzcard','humo','other'
] as const

// Currencies (from your migration)
const CURRENCIES = [
  'USD','EUR','GBP','JPY','AUD','CAD','CHF','CNY','INR','BRL','ZAR','BDT','other'
] as const

// Initialize form with existing account data
const form = useForm({
  code: props.account.code ?? '',
  swift_code: props.account.swift_code ?? null,
  name: props.account.name ?? '',
  account_name: props.account.account_name ?? null,

  currency: props.account.currency ?? 'USD',
  opening_balance: props.account.opening_balance ?? 0,
  current_balance: props.account.current_balance ?? 0,

  is_active: props.account.is_active ?? true,
  type: props.account.type ?? 'bank',

  card_type: props.account.card_type ?? 'other',
  account_number: props.account.account_number ?? null,
  bank_name: props.account.bank_name ?? null,
  bank_routing_number: props.account.bank_routing_number ?? null,

  card_valid_from: props.account.card_valid_from ?? null, // YYYY-MM-DD
  card_expiry: props.account.card_expiry ?? null,
  card_cvv: null as string | null,  // do not preload sensitive values
  card_pin: null as string | null,  // do not preload sensitive values

  bank_iban: props.account.bank_iban ?? null,
  bank_address: props.account.bank_address ?? null,
  description: props.account.description ?? null,
})

const isCard = computed(() => form.type === 'card')

// Clear card secrets when switching away from "card"
watch(() => form.type, (t) => {
  if (t !== 'card') {
    form.card_type = 'other'
    form.card_valid_from = null
    form.card_expiry = null
    form.card_cvv = null
    form.card_pin = null
  }
})

// If current_balance is empty/zero, keep it in sync with opening_balance edits
watch(() => form.opening_balance, (v) => {
  const curr = Number(form.current_balance || 0)
  if (!curr) form.current_balance = v ?? 0
})

// Keep CODE uppercase and sluggy
function onCodeInput(e: Event) {
  const v = (e.target as HTMLInputElement).value
  form.code = v.toUpperCase().replace(/\s+/g, '-')
}

function submit() {
  form.put(route('accounts.update', props.account.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Clear sensitive fields after successful update
      form.reset('card_cvv', 'card_pin')
    },
  })
}
</script>

<template>
  <Head :title="`Edit Account • ${props.account.name}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Edit Account</h1>
        <div class="flex gap-2">
          <Link :href="route('accounts.index')" as="button" class="rounded-lg border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800">
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
        <!-- Basics -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Basics</h2>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium">Code <span class="text-red-500">*</span></label>
              <input
                type="text"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.code ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
                :value="form.code"
                @input="onCodeInput"
              />
              <p v-if="form.errors.code" class="mt-1 text-xs text-red-600">{{ form.errors.code }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Name <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-lg border px-3 py-2"
                :class="form.errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-700'"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Account Name</label>
              <input
                v-model="form.account_name"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.account_name" class="mt-1 text-xs text-red-600">{{ form.errors.account_name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Type <span class="text-red-500">*</span></label>
              <select
                v-model="form.type"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              >
                <option value="bank">Bank</option>
                <option value="mobile">Mobile Wallet</option>
                <option value="card">Card</option>
              </select>
              <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Currency <span class="text-red-500">*</span></label>
              <select
                v-model="form.currency"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              >
                <option v-for="c in CURRENCIES" :key="c" :value="c">{{ c }}</option>
              </select>
              <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">{{ form.errors.currency }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Opening Balance</label>
              <input
                v-model.number="form.opening_balance"
                type="number" step="0.01" inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
                placeholder="0.00"
              />
              <p v-if="form.errors.opening_balance" class="mt-1 text-xs text-red-600">{{ form.errors.opening_balance }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Current Balance</label>
              <input
                v-model.number="form.current_balance"
                type="number" step="0.01" inputmode="decimal"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
                placeholder="0.00"
              />
              <p v-if="form.errors.current_balance" class="mt-1 text-xs text-red-600">{{ form.errors.current_balance }}</p>
            </div>

            <div class="flex items-center gap-2">
              <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4" />
              <label for="is_active" class="text-sm font-medium">Active</label>
            </div>
          </div>
        </section>

        <!-- Bank / Wallet -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Bank / Wallet Details
          </h2>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium">Account Number</label>
              <input
                v-model="form.account_number"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.account_number" class="mt-1 text-xs text-red-600">{{ form.errors.account_number }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Bank / Provider Name</label>
              <input
                v-model="form.bank_name"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.bank_name" class="mt-1 text-xs text-red-600">{{ form.errors.bank_name }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Routing Number</label>
              <input
                v-model="form.bank_routing_number"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.bank_routing_number" class="mt-1 text-xs text-red-600">{{ form.errors.bank_routing_number }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">SWIFT</label>
              <input
                v-model="form.swift_code"
                type="text"
                maxlength="11"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.swift_code" class="mt-1 text-xs text-red-600">{{ form.errors.swift_code }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">IBAN</label>
              <input
                v-model="form.bank_iban"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.bank_iban" class="mt-1 text-xs text-red-600">{{ form.errors.bank_iban }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-sm font-medium">Bank Address</label>
              <input
                v-model="form.bank_address"
                type="text"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.bank_address" class="mt-1 text-xs text-red-600">{{ form.errors.bank_address }}</p>
            </div>
          </div>
        </section>

        <!-- Card (conditional) -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Card</h2>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium">Card Brand</label>
              <select
                v-model="form.card_type"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700 capitalize"
              >
                <option v-for="t in CARD_TYPES" :key="t" :value="t" class="capitalize">
                  {{ t.replace('_',' ') }}
                </option>
              </select>
              <p v-if="form.errors.card_type" class="mt-1 text-xs text-red-600">{{ form.errors.card_type }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Valid From</label>
              <input
                v-model="form.card_valid_from"
                type="date"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.card_valid_from" class="mt-1 text-xs text-red-600">{{ form.errors.card_valid_from }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">Expiry</label>
              <input
                v-model="form.card_expiry"
                type="date"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.card_expiry" class="mt-1 text-xs text-red-600">{{ form.errors.card_expiry }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">CVV</label>
              <input
                v-model="form.card_cvv"
                type="password"
                autocomplete="off"
                placeholder="Leave blank to keep unchanged"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.card_cvv" class="mt-1 text-xs text-red-600">{{ form.errors.card_cvv }}</p>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium">PIN</label>
              <input
                v-model="form.card_pin"
                type="password"
                autocomplete="off"
                placeholder="Leave blank to keep unchanged"
                class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
              />
              <p v-if="form.errors.card_pin" class="mt-1 text-xs text-red-600">{{ form.errors.card_pin }}</p>
            </div>
          </div>
          <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
            For security, CVV/PIN are not pre-filled. Enter new values only if you want to change them.
          </p>
        </section>

        <!-- Notes -->
        <section class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
          <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</h2>
          <textarea
            v-model="form.description"
            rows="4"
            class="w-full rounded-lg border px-3 py-2 border-gray-300 dark:border-gray-700"
            placeholder="Optional notes..."
          />
          <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
        </section>
      </form>
    </div>
  </AppLayout>
</template>
