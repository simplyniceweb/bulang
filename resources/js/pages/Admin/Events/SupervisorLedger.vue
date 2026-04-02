<script setup>
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
  event: Object,
  transactions: Array,
  summary: Object,
});

// 💰 Money Formatter
const formatMoney = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

// Date Formatter
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};
</script>

<template>
  <div class="p-6 bg-gray-900 text-white min-h-screen">
    <Link 
        :href="route('admin.events.index')" 
        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm transition inline-block mb-6">
        ← Back to Events
    </Link>

    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-bold">Supervisor Ledger</h1>
            <p class="text-gray-400">Event: {{ event.name }}</p>
        </div>
        <div class="text-right text-xs text-gray-500 uppercase tracking-widest">
            Audit Log Active
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-10">
      <div class="bg-indigo-900 border border-indigo-700 p-4 rounded-lg shadow-lg">
        <p class="text-xs opacity-70 uppercase font-semibold">Supervisor Hand</p>
        <p class="text-xl font-bold text-indigo-100">
          {{ formatMoney(summary.supervisor_hand) }}
        </p>
      </div>

      <div class="bg-green-900 border border-green-700 p-4 rounded-lg shadow-lg">
        <p class="text-xs opacity-70 uppercase font-semibold">Circulated Money</p>
        <p class="text-xl font-bold text-green-100">
          {{ formatMoney(summary.circulated) }}
        </p>
      </div>

      <div class="bg-blue-900 border border-blue-700 p-4 rounded-lg shadow-lg">
        <p class="text-xs opacity-70 uppercase font-semibold">Total Cash In</p>
        <p class="text-xl font-bold text-blue-100">
          {{ formatMoney(summary.total_in) }}
        </p>
      </div>

      <div class="bg-red-900 border border-red-700 p-4 rounded-lg shadow-lg">
        <p class="text-xs opacity-70 uppercase font-semibold">Total Cash Out</p>
        <p class="text-xl font-bold text-red-100">
          {{ formatMoney(summary.total_out) }}
        </p>
      </div>
    </div>

    <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800 shadow-xl">
        <div class="p-4 border-b border-gray-700 bg-gray-800/50">
            <h2 class="text-lg font-semibold text-gray-200">Recent Cash Movements</h2>
        </div>
        
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wider">
                    <th class="p-4 text-left font-semibold">Time</th>
                    <th class="p-4 text-left font-semibold">Teller</th>
                    <th class="p-4 text-center font-semibold">Action</th>
                    <th class="p-4 text-right font-semibold">Amount</th>
                    <th class="p-4 text-center font-semibold">Wallet History</th>
                    <th class="p-4 text-right font-semibold">Authorized By</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-700">
                <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-gray-750 transition-colors">
                    <td class="p-4 text-gray-400 font-mono">
                        {{ formatDate(tx.created_at) }}
                    </td>
                    
                    <td class="p-4">
                        <div class="font-bold text-gray-200">{{ tx.teller?.name }}</div>
                        <div class="text-xs text-gray-500">ID: #{{ tx.teller_id }}</div>
                    </td>

                    <td class="p-4 text-center">
                        <span 
                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter"
                            :class="tx.type === 'cash_in' ? 'bg-green-600/20 text-green-400 border border-green-600/30' : 'bg-red-600/20 text-red-400 border border-red-600/30'"
                        >
                            {{ tx.type.replace('_', ' ') }}
                        </span>
                    </td>

                    <td class="p-4 text-right font-bold text-lg" 
                        :class="tx.type === 'cash_in' ? 'text-green-400' : 'text-red-400'">
                        {{ tx.type === 'cash_in' ? '+' : '-' }} {{ formatMoney(tx.amount) }}
                    </td>

                    <td class="p-4">
                        <div class="flex flex-col items-center text-[11px] font-mono leading-tight">
                            <span class="text-gray-500 italic">Prev: {{ formatMoney(tx.balance_before) }}</span>
                            <span class="text-gray-300 font-bold">New: {{ formatMoney(tx.balance_after) }}</span>
                        </div>
                    </td>

                    <td class="p-4 text-right text-gray-300">
                        <span class="bg-gray-700 px-2 py-1 rounded text-xs">
                            {{ tx.authorized_by?.name || 'Admin' }}
                        </span>
                    </td>
                </tr>

                <tr v-if="!transactions.length">
                    <td colspan="6" class="p-12 text-center text-gray-500 italic">
                        No cash transactions recorded for this event.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
  </div>
</template>

<style scoped>
.bg-gray-750 {
    background-color: #252f3f;
}
</style>