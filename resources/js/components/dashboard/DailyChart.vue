<script setup lang="ts">
import { computed, ref } from 'vue'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
)

interface DailyData {
    date: string // Y-m-d
    day: string
    income: number
    expenses: number
}

interface Props {
    dailyData: DailyData[]
    currency?: string
}

const props = withDefaults(defineProps<Props>(), {
    currency: 'BDT'
})

/**
 * period selection
 */
const period = ref<'this' | 'last'>('this')

/**
 * helpers
 */
const today = new Date()

const getMonthRange = (type: 'this' | 'last') => {
    const year = today.getFullYear()
    const month = today.getMonth()

    if (type === 'this') {
        return {
            start: new Date(year, month, 1),
            end: new Date(year, month + 1, 0, 23, 59, 59)
        }
    }

    return {
        start: new Date(year, month - 1, 1),
        end: new Date(year, month, 0, 23, 59, 59)
    }
}

/**
 * filtered data by month
 */
const filteredData = computed(() => {
    const { start, end } = getMonthRange(period.value)

    return props.dailyData.filter(item => {
        const d = new Date(item.date)
        return d >= start && d <= end
    })
})

/**
 * month label for UI
 */
const monthTitle = computed(() => {
    const date =
        period.value === 'this'
            ? today
            : new Date(today.getFullYear(), today.getMonth() - 1, 1)

    return date.toLocaleString('en-US', { month: 'long', year: 'numeric' })
})

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value)

/**
 * chart data
 */
const chartData = computed(() => ({
    labels: filteredData.value.map(item =>
        new Date(item.date).getDate().toString()
    ),
    datasets: [
        {
            label: 'Income',
            data: filteredData.value.map(i => i.income),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            pointRadius: 3
        },
        {
            label: 'Expenses',
            data: filteredData.value.map(i => i.expenses),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.4,
            pointRadius: 3
        }
    ]
}))
import type { ChartOptions } from 'chart.js'

const chartOptions = computed<ChartOptions<'line'>>(() => ({
  responsive: true,
  maintainAspectRatio: false,

  plugins: {
    title: {
      display: true,
      text: `Daily Income vs Expenses (${monthTitle.value})`,
      font: {
        size: 16,
        weight: 'bold' as const
      }
    },

    tooltip: {
      callbacks: {
        label: (ctx) =>
          `${ctx.dataset.label}: ${formatCurrency(ctx.parsed.y)}`
      }
    }
  },

  scales: {
    x: {
      title: {
        display: true,
        text: 'Day of Month'
      }
    },

    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => formatCurrency(Number(value))
      }
    }
  }
}))


/**
 * summary stats
 */
const totalIncome = computed(() =>
    filteredData.value.reduce((s, i) => s + i.income, 0)
)

const totalExpenses = computed(() =>
    filteredData.value.reduce((s, i) => s + i.expenses, 0)
)

const netAmount = computed(() => totalIncome.value - totalExpenses.value)
</script>
<template>
  <div
    class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border
           p-6 bg-white dark:bg-gray-900"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ monthTitle }}
      </h3>

      <div class="flex gap-2">
        <button
          @click="period = 'this'"
          :class="[
            'px-3 py-1 text-sm rounded-md transition-colors',
            period === 'this'
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
          ]"
        >
          This Month
        </button>

        <button
          @click="period = 'last'"
          :class="[
            'px-3 py-1 text-sm rounded-md transition-colors',
            period === 'last'
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
          ]"
        >
          Last Month
        </button>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="text-center p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
        <div class="text-sm font-medium text-green-600 dark:text-green-400">
          Total Income
        </div>
        <div class="text-lg font-bold text-green-700 dark:text-green-300">
          {{ formatCurrency(totalIncome) }}
        </div>
      </div>

      <div class="text-center p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
        <div class="text-sm font-medium text-red-600 dark:text-red-400">
          Total Expenses
        </div>
        <div class="text-lg font-bold text-red-700 dark:text-red-300">
          {{ formatCurrency(totalExpenses) }}
        </div>
      </div>

      <div
        class="text-center p-3 rounded-lg"
        :class="
          netAmount >= 0
            ? 'bg-blue-50 dark:bg-blue-900/20'
            : 'bg-orange-50 dark:bg-orange-900/20'
        "
      >
        <div
          class="text-sm font-medium"
          :class="
            netAmount >= 0
              ? 'text-blue-600 dark:text-blue-400'
              : 'text-orange-600 dark:text-orange-400'
          "
        >
          Net Amount
        </div>
        <div
          class="text-lg font-bold"
          :class="
            netAmount >= 0
              ? 'text-blue-700 dark:text-blue-300'
              : 'text-orange-700 dark:text-orange-300'
          "
        >
          {{ formatCurrency(netAmount) }}
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="h-80">
      <!-- IMPORTANT: Line must be PascalCase -->
      <Line
        :data="chartData"
        :options="chartOptions"
        class="w-full h-full"
      />
    </div>
  </div>
</template>

