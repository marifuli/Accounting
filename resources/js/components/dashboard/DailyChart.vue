<script setup lang="ts">
import { computed, ref } from 'vue';
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
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface DailyData {
  date: string;
  day: string;
  income: number;
  expenses: number;
}

interface Props {
  dailyData: DailyData[];
  currency?: string;
}

const props = withDefaults(defineProps<Props>(), {
  currency: 'BDT'
});

const period = ref<'30' | '60'>('30');

const filteredData = computed(() => {
  const days = period.value === '30' ? 30 : 60;
  return props.dailyData.slice(-days);
});

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-BD', {
    style: 'currency',
    currency: props.currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);
};

const chartData = computed(() => ({
  labels: filteredData.value.map(item => item.day),
  datasets: [
    {
      label: 'Income',
      data: filteredData.value.map(item => item.income),
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      fill: false,
      tension: 0.4,
      pointBackgroundColor: '#10b981',
      pointBorderColor: '#10b981',
      pointRadius: 3,
      pointHoverRadius: 5,
    },
    {
      label: 'Expenses',
      data: filteredData.value.map(item => item.expenses),
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      fill: false,
      tension: 0.4,
      pointBackgroundColor: '#ef4444',
      pointBorderColor: '#ef4444',
      pointRadius: 3,
      pointHoverRadius: 5,
    }
  ]
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index' as const,
    intersect: false,
  },
  plugins: {
    title: {
      display: true,
      text: `Daily Income vs Expenses (Last ${period.value} days)`,
      color: '#374151',
      font: {
        size: 16,
        weight: 'bold' as const
      }
    },
    legend: {
      display: true,
      position: 'top' as const,
      labels: {
        color: '#6b7280',
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.95)',
      titleColor: '#f9fafb',
      bodyColor: '#f9fafb',
      borderColor: '#374151',
      borderWidth: 1,
      cornerRadius: 8,
      displayColors: true,
      callbacks: {
        label: function(context: any) {
          const label = context.dataset.label || '';
          const value = formatCurrency(context.parsed.y);
          return `${label}: ${value}`;
        }
      }
    }
  },
  scales: {
    x: {
      display: true,
      title: {
        display: true,
        text: 'Date',
        color: '#6b7280'
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      },
      ticks: {
        color: '#6b7280',
        maxTicksLimit: period.value === '30' ? 15 : 10
      }
    },
    y: {
      display: true,
      title: {
        display: true,
        text: `Amount (${props.currency})`,
        color: '#6b7280'
      },
      grid: {
        color: 'rgba(107, 114, 128, 0.1)'
      },
      ticks: {
        color: '#6b7280',
        callback: function(value: any) {
          return formatCurrency(value);
        }
      },
      beginAtZero: true
    }
  },
  elements: {
    line: {
      borderWidth: 2
    },
    point: {
      hoverBorderWidth: 3
    }
  }
}));

const totalIncome = computed(() =>
  filteredData.value.reduce((sum, item) => sum + item.income, 0)
);

const totalExpenses = computed(() =>
  filteredData.value.reduce((sum, item) => sum + item.expenses, 0)
);

const netAmount = computed(() => totalIncome.value - totalExpenses.value);
</script>

<template>
  <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-6 bg-white dark:bg-gray-900">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Trends</h3>
      <div class="flex gap-2">
        <button
          @click="period = '30'"
          :class="[
            'px-3 py-1 text-sm rounded-md transition-colors',
            period === '30'
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
          ]"
        >
          30 Days
        </button>
        <button
          @click="period = '60'"
          :class="[
            'px-3 py-1 text-sm rounded-md transition-colors',
            period === '60'
              ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'
          ]"
        >
          60 Days
        </button>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="text-center p-3 rounded-lg bg-green-50 dark:bg-green-900/20">
        <div class="text-sm text-green-600 dark:text-green-400 font-medium">Total Income</div>
        <div class="text-lg font-bold text-green-700 dark:text-green-300">
          {{ formatCurrency(totalIncome) }}
        </div>
      </div>

      <div class="text-center p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
        <div class="text-sm text-red-600 dark:text-red-400 font-medium">Total Expenses</div>
        <div class="text-lg font-bold text-red-700 dark:text-red-300">
          {{ formatCurrency(totalExpenses) }}
        </div>
      </div>

      <div class="text-center p-3 rounded-lg" :class="netAmount >= 0 ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-orange-50 dark:bg-orange-900/20'">
        <div class="text-sm font-medium" :class="netAmount >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400'">
          Net Amount
        </div>
        <div class="text-lg font-bold" :class="netAmount >= 0 ? 'text-blue-700 dark:text-blue-300' : 'text-orange-700 dark:text-orange-300'">
          {{ formatCurrency(netAmount) }}
        </div>
      </div>
    </div>

    <!-- Chart -->
    <div class="h-80">
      <Line
        :data="chartData"
        :options="chartOptions"
        class="w-full h-full"
      />
    </div>
  </div>
</template>
