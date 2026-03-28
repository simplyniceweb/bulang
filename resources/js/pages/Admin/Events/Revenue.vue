<template>
  <div class="p-6 bg-gray-900 text-white">

    <Link 
        :href="route('admin.events.index')"
        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm">
        ← Back to Events
    </Link>

    <h1 class="text-2xl font-bold my-6">
      Revenue Report: {{ event.name }}
    </h1>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-3 gap-6 mb-8">
      <div class="p-4 bg-blue-800 rounded-lg">
        <p class="text-sm opacity-70">
          Total Plasada ({{ event.house_percent }}%)
        </p>
        <p class="text-3xl font-bold">
          {{ formatMoney(total_plasada) }}
        </p>
      </div>

      <div class="p-4 bg-yellow-700 rounded-lg">
        <p class="text-sm opacity-70">Draw Net Profit</p>
        <p class="text-3xl font-bold">
          {{ formatMoney(total_draw_income) }}
        </p>
      </div>

      <div class="p-4 bg-green-700 rounded-lg">
        <p class="text-sm opacity-70">Total Net Revenue</p>
        <p class="text-3xl font-bold">
          {{ formatMoney(total_revenue) }}
        </p>
      </div>
    </div>

    <!-- TABLE -->
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-gray-700 uppercase text-xs">
          <th class="p-3">Round #</th>
            <th class="p-3">Meron</th>
            <th class="p-3">Wala</th>
          <th class="p-3">Plasada</th>
          <th class="p-3">Draw Income</th>
          <th class="p-3 text-right">Total Income + Draw</th>
        </tr>
      </thead>

      <tbody>
        <tr
          v-for="item in breakdown"
          :key="item.round_number"
          class="border-b border-gray-800"
        >
            <td class="p-3">Fight {{ item.round_number }}</td>

            <td class="p-3 text-blue-300">
            {{ formatMoney(item.total_meron) }}
            </td>

            <td class="p-3 text-red-300">
            {{ formatMoney(item.total_wala) }}
            </td>

            <td class="p-3 text-blue-400">
            {{ formatMoney(item.plasada) }}
            </td>

            <td class="p-3 text-yellow-400">
            {{ formatMoney(item.draw_profit) }}
            </td>

            <td class="p-3 text-right font-bold">
            {{ formatMoney(item.total_round_income) }}
            </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  event: Object,
  breakdown: Array,
  total_revenue: Number,
  total_plasada: Number,
  total_draw_income: Number,
});

// ✅ Safe money formatter
const formatMoney = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};
</script>