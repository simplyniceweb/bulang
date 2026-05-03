<script setup>
import { ref } from 'vue'
import { route } from 'ziggy-js'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  event: Object,
  tellers: Array,
})

const expanded = ref({})
const ledgers = ref({})
const loading = ref({})
const summaries = ref({})

const formatMoney = (val) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(val || 0)
}

const toggleTeller = async (tellerId) => {
  expanded.value[tellerId] = !expanded.value[tellerId]

  if (!expanded.value[tellerId]) return

  if (ledgers.value[tellerId]) return

  loading.value[tellerId] = true

  try {
    const response = await axios.get(
      route('admin.teller.ledger.details', {
        event: props.event.id,
        teller: tellerId
      })
    )

    // ledgers.value[tellerId] = response.data
    ledgers.value[tellerId] = response.data.ledger
    summaries.value[tellerId] = response.data.summary
  } catch (error) {
    console.error('Failed to load teller ledger:', error)
    ledgers.value[tellerId] = []
  } finally {
    loading.value[tellerId] = false
  }
}

const resultClass = (result) => {
  if (result.includes('PAID')) return 'bg-green-600/20 text-green-400'
  if (result.includes('UNPAID')) return 'bg-yellow-600/20 text-yellow-400'
  if (result === 'LOSE') return 'bg-red-600/20 text-red-400'
  if (result === 'REFUND') return 'bg-blue-600/20 text-blue-400'
  return 'bg-gray-600/20 text-gray-300'
}
</script>

<template>
  <div class="p-6 bg-gray-900 text-white min-h-screen">
    
    <!-- BACK BUTTON -->
    <Link 
      :href="route('admin.events.index')" 
      class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm transition inline-block mb-6">
      ← Back to Events
    </Link>

    <!-- HEADER -->
    <div class="flex justify-between items-end mb-8">
      <div>
        <h1 class="text-3xl font-bold">Teller Ledger</h1>
        <p class="text-gray-400">Event: {{ event.name }}</p>
      </div>

      <div class="text-right text-xs text-gray-500 uppercase tracking-widest">
        Audit Log Active
      </div>
    </div>

    <!-- TELLERS -->
    <div class="space-y-4">

      <div
        v-for="teller in tellers"
        :key="teller.id"
        class="border border-gray-700 rounded-lg overflow-hidden bg-gray-800 shadow-xl"
      >

        <!-- TELLER HEADER -->
        <div
          @click="toggleTeller(teller.id)"
          class="p-4 border-b border-gray-700 bg-gray-800/50 flex justify-between items-center cursor-pointer hover:bg-gray-700 transition"
        >
          <div>
            <h2 class="text-lg font-semibold text-gray-200">
              {{ teller.name }}
            </h2>
            <p class="text-xs text-gray-500">
              Teller ID: #{{ teller.id }}
            </p>
          </div>

          <div class="text-sm text-gray-400">
            {{ expanded[teller.id] ? '▲ Hide Ledger' : '▼ View Ledger' }}
          </div>
        </div>

        <!-- LOADING -->
        <div
          v-if="expanded[teller.id] && loading[teller.id]"
          class="p-6 text-center text-gray-400"
        >
          Loading ledger...
        </div>

        <!-- TABLE -->
        <div v-if="expanded[teller.id] && !loading[teller.id]">
            <div class="mt-3 grid grid-cols-2 md:grid-cols-6 gap-3 p-4 border-b border-gray-700">

            <div class="bg-blue-900/40 p-3 rounded">
                <p class="text-xs">Bet</p>
                <p class="font-bold text-blue-300">
                {{ formatMoney(summaries[teller.id]?.total_bet) }}
                </p>
            </div>

            <div class="bg-red-900/40 p-3 rounded">
                <p class="text-xs">Paid</p>
                <p class="font-bold text-red-300">
                {{ formatMoney(summaries[teller.id]?.total_paid) }}
                </p>
            </div>

            <div class="bg-yellow-900/40 p-3 rounded">
                <p class="text-xs">Unpaid</p>
                <p class="font-bold text-yellow-300">
                {{ formatMoney(summaries[teller.id]?.unpaid_liability) }}
                </p>
            </div>

            <div class="bg-indigo-900/40 p-3 rounded">
                <p class="text-xs">Expected</p>
                <p class="font-bold text-indigo-300">
                {{ formatMoney(summaries[teller.id]?.expected_cash) }}
                </p>
            </div>

            <div class="bg-gray-700/40 p-3 rounded">
                <p class="text-xs">Actual</p>
                <p class="font-bold text-gray-200">
                {{ formatMoney(summaries[teller.id]?.actual_wallet) }}
                </p>
            </div>

            <div class="bg-purple-900/40 p-3 rounded">
                <p class="text-xs">Variance</p>
                <p 
                class="font-bold"
                :class="summaries[teller.id]?.variance >= 0 ? 'text-green-400' : 'text-red-400'"
                >
                {{ formatMoney(summaries[teller.id]?.variance) }}
                </p>
            </div>

            </div>

            <table class="w-full text-sm border-collapse">
                <thead>
                <tr class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wider">
                    <th class="p-3 text-left">Time</th>
                    <th class="p-3 text-center">Round</th>
                    <th class="p-3 text-left">Ticket</th>
                    <th class="p-3 text-center">Side</th>
                    <th class="p-3 text-center">Winner</th>
                    <th class="p-3 text-center">Status</th>
                    <th class="p-3 text-right">Amount</th>
                    <th class="p-3 text-right">Net</th>
                    <th class="p-3 text-right">Balance</th>
                </tr>
                </thead>

                <tbody class="divide-y divide-gray-700">
                <tr
                    v-for="row in ledgers[teller.id]"
                    :key="row.ticket_number"
                    class="hover:bg-gray-750 transition"
                >
                    <td class="p-3 text-gray-400 font-mono">
                    {{ row.time }}
                    </td>

                    <td class="p-3 text-center text-gray-300">
                    {{ row.round }}
                    </td>

                    <td class="p-3 font-bold text-gray-200">
                    {{ row.ticket_number }}
                    </td>

                    <td class="p-3 text-center uppercase text-xs tracking-wider">
                    {{ row.side }}
                    </td>

                    <td class="p-3 text-center uppercase text-xs font-bold tracking-wider"
                        :class="{
                        'text-green-400': row.winner === row.side,
                        'text-red-400':   row.winner && row.winner !== row.side,
                        'text-gray-500':  !row.winner
                        }">
                    {{ row.winner ?? '—' }}
                    </td>

                    <!-- STATUS -->
                    <td class="p-3 text-center">
                    <span
                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                        :class="resultClass(row.result)"
                    >
                        {{ row.result }}
                    </span>
                    </td>

                    <td class="p-3 text-right">
                    {{ formatMoney(row.amount) }}
                    </td>

                    <!-- NET -->
                    <td
                    class="p-3 text-right font-bold"
                    :class="row.net >= 0 ? 'text-green-400' : 'text-red-400'"
                    >
                    {{ row.net >= 0 ? '+' : '-' }}
                    {{ formatMoney(Math.abs(row.net)) }}
                    </td>

                    <!-- BALANCE -->
                    <td class="p-3 text-right font-mono text-gray-200">
                    {{ formatMoney(row.running_balance) }}
                    </td>
                </tr>

                <tr v-if="!ledgers[teller.id] || ledgers[teller.id].length === 0">
                    <td colspan="8" class="p-8 text-center text-gray-500 italic">
                    No ledger records found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

      </div>

    </div>
  </div>
</template>