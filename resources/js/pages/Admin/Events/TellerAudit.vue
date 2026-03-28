<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js';

const props = defineProps({
  event: Object,
  rounds: Array,
  tellerBreakdown: Object, // grouped by round_number
});

// 💰 Safe money formatter
const formatMoney = (value) => {
  return `₱${Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

const showBreakdown = ref({})
const breakdowns = ref({})
const loadingBreakdowns = ref({})

const toggleBreakdown = async (roundId) => {
  if (!breakdowns.value[roundId]) {
    await fetchBreakdown(roundId)
  }

  showBreakdown.value[roundId] = !showBreakdown.value[roundId]
}

const fetchBreakdown = async (roundId) => {
  // If already loaded, do nothing
  if (breakdowns.value[roundId]) return

  loadingBreakdowns.value[roundId] = true

  try {
    const response = await axios.get(`/admin/events/teller/${roundId}/breakdown`)

    breakdowns.value[roundId] = response.data
  } catch (error) {
    console.error(error)
  } finally {
    loadingBreakdowns.value[roundId] = false
  }
}
</script>

<template>
  <div class="p-6 bg-gray-900 text-white min-h-screen">

    <Link 
        :href="route('admin.events.index')"
        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm">
        ← Back to Events
    </Link>

    <!-- HEADER -->
    <h1 class="text-2xl font-bold my-6">
      Event: {{ event.name }}
    </h1>

    <!-- ROUNDS -->
    <div
      v-for="round in rounds"
      :key="round.id"
      class="mb-10 border border-gray-700 rounded-lg p-4"
    >
      <!-- ROUND TITLE -->
      <h2 class="text-xl font-semibold mb-4">
        Round {{ round.round_number }}
      </h2>

      <!-- ROUND SUMMARY -->
      <div class="grid grid-cols-4 gap-4 mb-4">
        <div class="bg-blue-800 p-3 rounded">
          <p class="text-xs opacity-70">Meron Total</p>
          <p class="text-lg font-bold">
            {{ formatMoney(round.total_meron) }}
          </p>
        </div>

        <div class="bg-yellow-800 p-3 rounded">
          <p class="text-xs opacity-70">Draw Total</p>
          <p class="text-lg font-bold">
            {{ formatMoney(round.total_draw) }}
          </p>
        </div>

        <div class="bg-red-800 p-3 rounded">
          <p class="text-xs opacity-70">Wala Total</p>
          <p class="text-lg font-bold">
            {{ formatMoney(round.total_wala) }}
          </p>
        </div>

        <div class="bg-green-700 p-3 rounded">
          <p class="text-xs opacity-70">Round Revenue</p>
          <p class="text-lg font-bold">
            {{ formatMoney(round.total_round_income) }}
          </p>
        </div>
      </div>


        <button
        class="px-3 py-1 rounded"
        :class="loadingBreakdowns[round.id] ? 'bg-gray-500 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
        :disabled="loadingBreakdowns[round.id]"
        @click="toggleBreakdown(round.id)"
        >
        {{ showBreakdown[round.id] ? 'Hide' : 'Show' }} Breakdown
        </button>

        <div v-if="loadingBreakdowns[round.id]" class="mt-3 text-gray-400">
        Loading...
        </div>
      <!-- TELLER BREAKDOWN -->
        <div v-else-if="showBreakdown[round.id] && breakdowns[round.id]" class="mt-4">
            <h3 class="text-md font-semibold mb-2">
            Teller Breakdown
            </h3>

            <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-xs uppercase">
                <th class="p-2 text-left">Teller</th>
                <th class="p-2">Tickets</th>
                <th class="p-2">Meron</th>
                <th class="p-2">Wala</th>
                <th class="p-2">Draw</th>
                <th class="p-2 text-right">Total Bet</th>
                </tr>
            </thead>

            <tbody>
                <tr
                v-for="teller in (breakdowns[round.id] || [])"
                :key="teller.teller_id"
                class="border-b border-gray-800"
                >
                <td class="p-2">#{{ teller.teller_id }}</td>

                <td class="p-2 text-center">
                    {{ teller.ticket_count }}
                </td>

                <td class="p-2 text-blue-300 text-center">
                    {{ formatMoney(teller.meron_total) }}
                </td>

                <td class="p-2 text-red-300 text-center">
                    {{ formatMoney(teller.wala_total) }}
                </td>
                <td class="p-2 text-yellow-400 text-center">
                {{ formatMoney(teller.draw_total) }}
                </td>
                <td class="p-2 text-right font-bold">
                    {{ formatMoney(teller.total_bet) }}
                </td>
                </tr>

                <!-- EMPTY STATE -->
                <tr v-if="!(breakdowns[round.id]?.length)">
                <td colspan="5" class="p-3 text-center text-gray-400">
                    No teller data
                </td>
                </tr>
            </tbody>
            </table>

            <div class="mt-3 text-right text-sm text-gray-400">
            Total Tickets:
            {{
                (breakdowns[round.id] || [])
                .reduce((sum, t) => sum + Number(t.ticket_count), 0)
            }}
            </div>
        </div>
    </div>
  </div>
</template>