<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import ErrorModal from '@/components/ErrorModal.vue'
import { useErrorModal } from '@/composables/useErrorModal'
import { formatCurrency, formatNumber } from '@/helpers/format';
import { logout } from '@/routes';

const {
  showErrorModal,
  errorMessage,
  closeErrorModal
} = useErrorModal()

/* --- Current Round --- */
const round = reactive({
  id: 0,
  status: 'open', // open | closed
  winner: null as 'wala' | 'meron' | 'draw' | null,
  totalWala: Math.ceil(Math.random() * 10000),
  totalMeron: Math.ceil(Math.random() * 10000),
  totalDraw: Math.ceil(Math.random() * 10000),
  wala_closed: false,
  meron_closed: false,
})

/* --- Winner selection --- */
const winner = ref<'wala' | 'meron' | 'draw' | ''>('')

/* --- Mock Recent Rounds --- */
const recentRounds = ref([
  { id: 10, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 9, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 8, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 7, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 6, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 5, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 4, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 3, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
  { id: 2, winner: 'wala', totalWala: 5000, totalMeron: 2000, totalDraw: 500, tellerTotals: [{name: 'Teller 1', wala: 2000, meron: 500, draw: 100}, {name: 'Teller 2', wala: 3000, meron: 1500, draw: 400}] },
  { id: 1, winner: 'meron', totalWala: 3000, totalMeron: 4000, totalDraw: 200, tellerTotals: [{name: 'Teller 1', wala: 1500, meron: 2000, draw: 100}, {name: 'Teller 2', wala: 1500, meron: 2000, draw: 100}] },
])

/* --- Actions --- */
function startRound(action: number) {
  if (action === 1) {

    if (!confirm('Open new round?')) return

    router.post(route('game_master.round.open'))

  }

  if (action === 0) {

    if (!confirm('Cancel round?')) return

    router.post(route('game_master.round.cancel', round.id))

  }
}

function endRound(selectedWinner: 'wala' | 'meron' | 'draw') {
  if (!selectedWinner) return alert('Please select winner')

  if (!confirm(`Confirm ending round #${round.id} with winner: ${selectedWinner.toUpperCase()}?`)) return;

  round.status = 'closed'
  round.winner = selectedWinner
  const selectedRound = recentRounds.value[Math.floor(Math.random() * recentRounds.value.length)];
  recentRounds.value.unshift(selectedRound) // Add random recent round to top of list for demo purposes

  startRound(2) // Automatically start new round after ending current round
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
  <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-6 h-screen items-start">
    
    <!-- LEFT PANEL: Round Controls -->
    <div class="lg:col-span-2 flex flex-col gap-6">
        <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-6">
        
        <div class="flex flex-col gap-4 border-b pb-6">
            <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900">Current Round: #{{ round.id }}</h2>
            <p class="font-semibold">Status: 
                <span :class="round.status==='open'?'text-green-600':'text-red-600'">{{ round.status.toUpperCase() }}</span>
            </p>
            </div>
            <button
            @click="startRound(1)"
            class="bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg"
            >
            OPEN NEW ROUND
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="flex flex-col gap-4 border-r pr-0 md:pr-8">
            <h3 class="font-bold text-gray-700 uppercase text-sm tracking-wider">Declare Result</h3>
            <div class="flex flex-col gap-3">
                <button
                @click="endRound('wala')"
                class="w-full py-3 rounded-lg text-white font-semibold transition bg-indigo-600 hover:bg-indigo-700"
                >
                WINNER WALA
                </button>

                <button
                @click="endRound('meron')"
                class="w-full py-3 rounded-lg text-white font-semibold transition bg-red-600 hover:bg-red-700"
                >
                WINNER MERON
                </button>

                <button
                @click="endRound('draw')"
                class="w-full py-3 rounded-lg text-white font-semibold transition bg-yellow-600 hover:bg-yellow-700"
                >
                WINNER DRAW
                </button>
            </div>

            <div class="mt-4 bg-gray-50 p-4 rounded-xl grid grid-cols-3 text-center font-bold">
                <div class="text-indigo-600 text-sm">WALA<br><span class="text-lg">{{ formatNumber(round.totalWala) }}</span></div>
                <div class="text-red-600 text-sm">MERON<br><span class="text-lg">{{ formatNumber(round.totalMeron) }}</span></div>
                <div class="text-yellow-600 text-sm">DRAW<br><span class="text-lg">{{ formatNumber(round.totalDraw) }}</span></div>
            </div>
            </div>

            <div class="flex flex-col gap-4">
            <h3 class="font-bold text-gray-700 uppercase text-sm tracking-wider">Betting Controls</h3>
            <div class="flex flex-col gap-3">
                <button
                @click="closeBetting('wala')"
                :disabled="round.wala_closed"
                class="w-full py-3 rounded-lg text-white font-semibold transition"
                :class="round.wala_closed ? 'bg-gray-300' : 'bg-indigo-600 hover:bg-indigo-700'"
                >
                CLOSE WALA
                </button>

                <button
                @click="closeBetting('meron')"
                :disabled="round.meron_closed"
                class="w-full py-3 rounded-lg text-white font-semibold transition"
                :class="round.meron_closed ? 'bg-gray-300' : 'bg-red-600 hover:bg-red-700'"
                >
                CLOSE MERON
                </button>

                <div class="mt-auto pt-4">
                <button
                    @click="startRound(0)"
                    class="w-full bg-gray-200 text-gray-600 py-2 rounded-lg font-bold hover:bg-red-100 hover:text-red-600 transition"
                >
                    CANCEL CURRENT ROUND
                </button>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>

    <!-- RIGHT PANEL: Recent Rounds + Teller Totals -->
    <div class="bg-white rounded-2xl shadow p-6 flex flex-col max-h-[calc(100vh-2rem)] sticky top-4">

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
            <span :class="{'text-indigo-600': r.winner==='wala','text-red-600': r.winner==='meron','text-yellow-500': r.winner==='draw'}" class="font-bold">{{ r.winner?.toUpperCase() }}</span>
          </div>

          <div class="grid grid-cols-3 text-center font-semibold border-t pt-1">
            <div class="text-indigo-600">WALA: {{ formatCurrency(r.totalWala) }}</div>
            <div class="text-red-600">MERON: {{ formatCurrency(r.totalMeron) }}</div>
            <div class="text-yellow-500">DRAW: {{ formatCurrency(r.totalDraw) }}</div>
          </div>

          <!-- Teller Totals -->
          <div v-if="r.tellerTotals?.length" class="mt-2 border-t pt-2 space-y-1 text-sm">
            <div v-for="t in r.tellerTotals" :key="t.name" class="flex justify-between px-2">
              <span>{{ t.name }}</span>
              <span class="flex gap-3">
                <span class="text-indigo-600 border-e pr-2">{{ formatCurrency(t.wala) }}</span>
                <span class="text-red-600 border-e pr-2">{{ formatCurrency(t.meron) }}</span>
                <span class="text-yellow-500 border-e pr-2">{{ formatCurrency(t.draw) }}</span>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ErrorModal
    :show="showErrorModal"
    :message="errorMessage"
    @close="closeErrorModal"
    />
  </div>
</template>
