<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Stats {
    totalBalance: number;
    totalIncome: number;
    totalExpenses: number;
    accountsCount: number;
    upcomingIncome: number;
    upcomingExpenses: number;
    netIncome: number;
    primaryCurrency: string;
}

interface Transaction {
    id: number;
    name: string;
    type: string;
    amount: number;
    category: string | null;
    date: string;
    from_account: string | null;
    to_account: string | null;
}

interface TransactionByCategory {
    category: string;
    count: number;
    amount: number;
}

interface MonthlyData {
    month: string;
    income: number;
    expenses: number;
}

interface AccountBalance {
    name: string;
    balance: number;
    original_balance: number;
    type: string;
    currency: string;
    primary_currency: string;
}

interface UpcomingItem {
    title: string;
    amount: number;
    type: string;
    date: string;
    days_until: number;
}

const props = defineProps<{
    stats: Stats;
    recentTransactions: Transaction[];
    transactionsByCategory: TransactionByCategory[];
    monthlyData: MonthlyData[];
    accountBalances: AccountBalance[];
    upcomingItems: UpcomingItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatCurrency = (amount: number, currency: string = 'BDT') => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2
    }).format(amount);
};

const formatBDTCurrency = (amount: number) => {
    return formatCurrency(amount, 'BDT');
};

const getTransactionTypeColor = (type: string) => {
    switch (type) {
        case 'inc':
            return 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20';
        case 'exp':
            return 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20';
        default:
            return 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20';
    }
};

const getAccountTypeIcon = (type: string) => {
    switch (type) {
        case 'bank':
            return '🏦';
        case 'card':
            return '💳';
        case 'mobile':
            return '📱';
        default:
            return '💰';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Balance ({{ stats.primaryCurrency }})</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatBDTCurrency(stats.totalBalance) }}</p>
                        </div>
                        <div class="text-2xl">💰</div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Income</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatBDTCurrency(stats.totalIncome) }}</p>
                        </div>
                        <div class="text-2xl">📈</div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Expenses</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatBDTCurrency(stats.totalExpenses) }}</p>
                        </div>
                        <div class="text-2xl">📉</div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Income</p>
                            <p class="text-2xl font-bold" :class="stats.netIncome >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                {{ formatBDTCurrency(stats.netIncome) }}
                            </p>
                        </div>
                        <div class="text-2xl">{{ stats.netIncome >= 0 ? '💹' : '📊' }}</div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Recent Transactions -->
                <div class="lg:col-span-2 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Transactions</h3>
                        <Link :href="route('transactions.index')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            View All
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="transaction in recentTransactions" :key="transaction.id"
                             class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getTransactionTypeColor(transaction.type)">
                                        {{ transaction.type === 'inc' ? 'Income' : 'Expense' }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ transaction.name }}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <span v-if="transaction.category">{{ transaction.category }} • </span>
                                    <span>{{ transaction.date }}</span>
                                </div>
                                <div v-if="transaction.from_account || transaction.to_account" class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    <span v-if="transaction.from_account">From: {{ transaction.from_account }}</span>
                                    <span v-if="transaction.from_account && transaction.to_account"> → </span>
                                    <span v-if="transaction.to_account">To: {{ transaction.to_account }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold" :class="transaction.type === 'inc' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ transaction.type === 'inc' ? '+' : '-' }}{{ formatBDTCurrency(transaction.amount) }}
                                </div>
                            </div>
                        </div>
                        <div v-if="recentTransactions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No transactions yet
                        </div>
                    </div>
                </div>

                <!-- Account Balances -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Account Balances</h3>
                        <Link :href="route('accounts.index')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            View All
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="account in accountBalances" :key="account.name"
                             class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="text-lg">{{ getAccountTypeIcon(account.type) }}</div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ account.name }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ account.type }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ formatBDTCurrency(account.balance) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ formatCurrency(account.original_balance, account.currency) }} → {{ account.primary_currency }}
                                </div>
                            </div>
                        </div>
                        <div v-if="accountBalances.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No accounts yet
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Items and Statistics -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Upcoming Items -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Upcoming (30 days)</h3>
                        <Link :href="route('upcomming-expense-income.index')" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            View All
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="item in upcomingItems" :key="item.title"
                             class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getTransactionTypeColor(item.type)">
                                        {{ item.type === 'inc' ? 'Income' : 'Expense' }}
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ item.title }}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ item.date }} • {{ item.days_until }} days
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold" :class="item.type === 'inc' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ formatBDTCurrency(item.amount) }}
                                </div>
                            </div>
                        </div>
                        <div v-if="upcomingItems.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No upcoming items
                        </div>
                    </div>
                </div>

                <!-- Transactions by Category -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Transactions by Category</h3>
                    <div class="space-y-3">
                        <div v-for="category in transactionsByCategory" :key="category.category"
                             class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900 dark:text-white">{{ category.category }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ category.count }} transactions</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ formatBDTCurrency(category.amount) }}</div>
                            </div>
                        </div>
                        <div v-if="transactionsByCategory.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No categories yet
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Data Summary -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Monthly Overview (Last 6 Months)</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="month in monthlyData" :key="month.month"
                         class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ month.month }}</div>
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-sm text-green-600 dark:text-green-400">Income:</span>
                                <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ formatBDTCurrency(month.income) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-red-600 dark:text-red-400">Expenses:</span>
                                <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ formatBDTCurrency(month.expenses) }}</span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-1">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Net:</span>
                                <span class="text-sm font-bold" :class="(month.income - month.expenses) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ formatBDTCurrency(month.income - month.expenses) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
