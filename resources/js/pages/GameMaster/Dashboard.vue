<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { formatCurrency } from '@/helpers/format';
import { logout } from '@/routes';

/* --- Current Round --- */
const round = reactive({
  id: 0,
  status: 'open', // open | closed
  winner: null as 'wala' | 'meron' | 'draw' | null,
  totalWala: 0,
  totalMeron: 0,
  totalDraw: 0,
  wala_closed: false,
  meron_closed: false,
})

/* --- Winner selection --- */
const winner = ref<'wala' | 'meron' | 'draw' | ''>('')

/* --- Mock Recent Rounds --- */
const recentRounds = ref([
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
])

/* --- Actions --- */
function startRound() {
  round.id++
  round.status = 'open'
  round.winner = null
  round.totalWala = 0
  round.totalMeron = 0
  round.totalDraw = 0
  round.wala_closed = false
  round.meron_closed = false
  winner.value = ''
}

function endRound(selectedWinner: 'wala' | 'meron' | 'draw') {
  if (!selectedWinner) return alert('Please select winner')
  round.status = 'closed'
  round.winner = selectedWinner
  recentRounds.value.unshift({ 
    id: round.id, 
    winner: selectedWinner, 
    totalWala: round.totalWala, 
    totalMeron: round.totalMeron, 
    totalDraw: round.totalDraw,
    tellerTotals: [] // fetch actual teller totals from backend
  })
}

function closeBetting(side: 'wala' | 'meron') {
  if (side === 'wala' && round.totalWala <= round.totalMeron) return alert('Cannot close WALA: not higher stake')
  if (side === 'meron' && round.totalMeron <= round.totalWala) return alert('Cannot close MERON: not higher stake')
  round[`${side}_closed`] = true
}

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
  <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-6 h-screen">
    
    <!-- LEFT PANEL: Round Controls -->
    <div class="lg:col-span-2 flex flex-col gap-6">
      <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-4">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Current Round: #{{ round.id }}</h2>
          <p class="font-semibold">Status: 
            <span :class="round.status==='open'?'text-green-600':'text-red-600'">{{ round.status.toUpperCase() }}</span>
          </p>
        </div>

        <!-- Start Round -->
        <button
          @click="startRound"
          class="bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition"
        >
          NEW ROUND
        </button>

        <!-- End Round -->
        <div class="mt-4 flex flex-col gap-2">
          <label class="font-semibold">End Round & Declare Winner</label>
          <select v-model="winner" class="border rounded-lg py-2 px-3 w-full">
            <option value="wala">Wala</option>
            <option value="meron">Meron</option>
            <option value="draw">Draw</option>
          </select>
          <button
            @click="endRound(winner as 'wala' | 'meron' | 'draw')"
            class="bg-indigo-600 text-white py-2 px-4 rounded-lg font-bold hover:bg-indigo-700 transition mt-2"
          >
            CONFIRM WINNER
          </button>
        </div>

        <!-- Close Betting -->
        <div class="mt-4 flex gap-3">
          <button
            @click="closeBetting('wala')"
            :disabled="round.wala_closed"
            class="flex-1 py-2 rounded-lg text-white font-semibold"
            :class="round.wala_closed ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'"
          >
            CLOSE WALA
          </button>

          <button
            @click="closeBetting('meron')"
            :disabled="round.meron_closed"
            class="flex-1 py-2 rounded-lg text-white font-semibold"
            :class="round.meron_closed ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700'"
          >
            CLOSE MERON
          </button>
        </div>

        <!-- Totals -->
        <div class="mt-4 border-t pt-3 grid grid-cols-3 text-center font-bold">
          <div class="text-green-600">WALA: {{ round.totalWala }}</div>
          <div class="text-red-600">MERON: {{ round.totalMeron }}</div>
          <div class="text-yellow-500">DRAW: {{ round.totalDraw }}</div>
        </div>
      </div>
    </div>

    <!-- RIGHT PANEL: Recent Rounds + Teller Totals -->
    <div class="bg-white rounded-2xl shadow p-6 flex flex-col h-full max-h-1/4">

        <!-- Profile -->
        <div class="flex justify-between items-center border-b pb-3">
            <div>
            <p class="font-bold text-lg"><CircleUserRound class="display-inline-block"/>  Jaylord Game Master</p>
            <p class="text-sm text-gray-500">Active Session</p>
            </div>
            <Link
                class="text-sm text-red-500 hover:underline cursor-pointer"
                :href="logout()"
                @click="handleLogout"
                as="button"
                data-test="logout-button"
            >
                Log out
            </Link>
        </div>

      <h3 class="text-lg font-bold mb-3">Recent Rounds</h3>
      <div class="flex-1 overflow-y-auto space-y-4">
        <div v-for="r in recentRounds" :key="r.id" class="border rounded-lg p-3 hover:bg-gray-50">
          <div class="flex justify-between items-center mb-2">
            <span class="font-bold">Round #{{ r.id }}</span>
            <span :class="{'text-green-600': r.winner==='wala','text-red-600': r.winner==='meron','text-yellow-500': r.winner==='draw'}" class="font-bold">{{ r.winner?.toUpperCase() }}</span>
          </div>

          <div class="grid grid-cols-3 text-center font-semibold border-t pt-1">
            <div class="text-green-600">WALA: {{ formatCurrency(r.totalWala) }}</div>
            <div class="text-red-600">MERON: {{ formatCurrency(r.totalMeron) }}</div>
            <div class="text-yellow-500">DRAW: {{ formatCurrency(r.totalDraw) }}</div>
          </div>

          <!-- Teller Totals -->
          <div v-if="r.tellerTotals?.length" class="mt-2 border-t pt-2 space-y-1 text-sm">
            <div v-for="t in r.tellerTotals" :key="t.name" class="flex justify-between px-2">
              <span>{{ t.name }}</span>
              <span class="flex gap-3">
                <span class="text-green-600 border-e pr-2">{{ formatCurrency(t.wala) }}</span>
                <span class="text-red-600 border-e pr-2">{{ formatCurrency(t.meron) }}</span>
                <span class="text-yellow-500 border-e pr-2">{{ formatCurrency(t.draw) }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
