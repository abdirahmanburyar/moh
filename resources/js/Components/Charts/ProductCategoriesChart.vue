<script>
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'ProductCategoriesChart',
  components: { Bar },
  props: {
    chartData: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#fff',
            titleColor: '#1f2937',
            bodyColor: '#1f2937',
            borderColor: '#e5e7eb',
            borderWidth: 1,
            padding: 12,
            displayColors: false,
            callbacks: {
              label: function(context) {
                return `${context.parsed.y} products`
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: {
              display: true,
              color: '#f3f4f6'
            },
            ticks: {
              stepSize: 1
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    }
  },
  computed: {
    chartDataFormatted() {
      const categories = Object.keys(this.chartData)
      const counts = Object.values(this.chartData)
      
      return {
        labels: categories,
        datasets: [
          {
            label: 'Products',
            backgroundColor: [
              'rgba(79, 70, 229, 0.8)',  // indigo
              'rgba(16, 185, 129, 0.8)',  // emerald
              'rgba(245, 158, 11, 0.8)'   // amber
            ],
            borderColor: [
              'rgba(79, 70, 229, 1)',
              'rgba(16, 185, 129, 1)',
              'rgba(245, 158, 11, 1)'
            ],
            borderWidth: 1,
            borderRadius: 6,
            data: counts
          }
        ]
      }
    }
  }
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-lg p-5 border border-gray-100 hover:shadow-xl transition-shadow duration-200">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Products by Category</h3>
    <div class="h-64">
      <Bar
        id="product-categories-chart"
        :options="chartOptions"
        :data="chartDataFormatted"
      />
    </div>
    <div class="mt-4 flex flex-wrap gap-4 justify-center">
      <div v-for="(count, category) in chartData" :key="category" class="flex items-center">
        <span 
          class="w-3 h-3 rounded-full mr-2" 
          :class="{
            'bg-indigo-500': category === 'Drugs',
            'bg-emerald-500': category === 'Consumables',
            'bg-amber-500': category === 'Lab'
          }"
        ></span>
        <span class="text-sm text-gray-600">{{ category }} ({{ count }})</span>
      </div>
    </div>
  </div>
</template>
