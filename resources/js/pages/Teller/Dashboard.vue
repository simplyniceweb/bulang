<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleUserRound } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';
import Toast from '@/components/Toast.vue';
import { tellerClock } from '@/helpers/time';
import { addToast } from '@/helpers/toast';
import { logout } from '@/routes';
import BetConfirm from './BetConfirm.vue';

// bet amount
const betAmount = ref(0);
const presets = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];
const setAmount = (value: number) => {
  betAmount.value = value
}
const clearAmount = () => {
  betAmount.value = 0
}

// bet confirmation
const side = ref<'meron' | 'wala' | 'draw'>('meron')
const capital = 10000;
const balance = ref(capital);
const showConfirm = ref(false)
const placeBet = (selectedSide: 'meron' | 'wala' | 'draw') => {
  side.value = selectedSide
  showConfirm.value = true
}
const confirmBet = () => {
  console.log('Bet confirmed:', betAmount.value, side.value)
  if (betAmount.value > balance.value) {
    addToast('Insufficient balance for this bet.', 'error')
    showConfirm.value = false
    return
  }

  if (betAmount.value <= 0) {
    addToast('Please enter a valid bet amount.', 'error')
    showConfirm.value = false
    return
  }

  balance.value -= betAmount.value
  addToast(`Bet ₱${betAmount.value} on ${side.value.toUpperCase()} confirmed!`, 'success')

  betAmount.value = 0
  showConfirm.value = false
}
const cancelBet = () => showConfirm.value = false


// For date and time
const now = ref(new Date())
let timer: number | null = null
const clock = ref(tellerClock(now.value))
onMounted(() => {
  timer = setInterval(() => {
    now.value = new Date()
    clock.value = tellerClock(now.value)
  }, 60000)
})
onUnmounted(() => clearInterval(timer as number))

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Toast />
    <Head title="Teller Dashboard" />
    <div class="h-[calc(100vh-80px)] p-4 bg-gray-100">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 h-full">

        <!-- ================= LEFT PANEL ================= -->
        <div class="lg:col-span-2 flex flex-col gap-4">

        <!-- ROUND HEADER -->
        <div class="bg-white rounded-2xl shadow p-4 flex justify-between items-center">
            <div>
            <p class="text-gray-500 text-sm">Current Round</p>
            <p class="text-4xl font-extrabold text-indigo-600">
                #12
            </p>
            </div>
            <div class="text-right">
            <p class="text-gray-500 text-sm">Date &amp; Time</p>
            <p class="text-sm font-bold text-red-500">
                {{ clock.date }}<br/>{{ clock.time }}
            </p>
            </div>
        </div>

        <!-- BET PANEL -->
        <div class="bg-white rounded-2xl shadow p-6 flex-1 flex flex-col">

            <!-- Amount Display -->
            <div class="mb-6">
            <input
                type="number"
                v-model="betAmount"
                placeholder="0"
                class="w-full text-5xl font-extrabold text-center border-2 border-gray-200 focus:border-indigo-500 focus:ring-0 rounded-2xl py-6"
            />
            </div>

            <!-- Preset Buttons -->
            <div class="grid grid-cols-5 gap-3 mb-8">
                <button
                    v-for="preset in presets"
                    :key="preset"
                    @click="setAmount(preset)"
                    :class="[
                        'font-semibold py-6 md:py-8 rounded-xl cursor-pointer text-xl md:text-3xl transition',
                        betAmount === preset
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-100 hover:bg-gray-200'
                    ]"
                >
                    {{ preset }}
                </button>
            </div>
            
            <!-- Clear amount -->
            <button
            @click="clearAmount"
            class="w-full bg-gray-800 hover:bg-gray-900 text-white
                    py-4 rounded-xl font-bold text-2xl
                    active:scale-95 transition"
            >
            CLEAR
            </button>

            <!-- Bet Buttons -->
            <div class="grid grid-cols-3 gap-4 mt-4">

            <button
                @click="placeBet('wala')"
                class="text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg
        transition transform duration-150
        bg-blue-600 hover:bg-blue-700 active:scale-95 cursor-pointer"
            >
                WALA
            </button>

            <button
                @click="placeBet('draw')"
                class="text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg
        transition transform duration-150
        bg-yellow-600 hover:bg-yellow-700 active:scale-95 cursor-pointer"
            >
                DRAW
            </button>

            <button
                @click="placeBet('meron')"
                class="text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg
        transition transform duration-150
        bg-red-600 hover:bg-red-700 active:scale-95 cursor-pointer"
            >
                MERON
            </button>
            <BetConfirm
                :visible="showConfirm"
                :amount="betAmount"
                :side="side"
                :remaining-balance="balance - betAmount"
                @confirm="confirmBet"
                @cancel="cancelBet"
            />
            </div>

        </div>
        </div>

        <!-- ================= RIGHT PANEL ================= -->
        <div class="bg-white rounded-2xl shadow p-4 flex flex-col h-full">

        <!-- Profile -->
        <div class="flex justify-between items-center border-b pb-3">
            <div>
            <p class="font-bold text-lg"><CircleUserRound class="display-inline-block"/>  Jaylord Teller</p>
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

        <!-- Capital Info -->
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
            <span>Assigned Capital</span>
            <span class="font-bold">₱ {{ capital }} </span>
            </div>
            <div class="flex justify-between">
            <span>Remaining</span>
            <span class="font-bold text-green-600">₱ {{ balance }}</span>
            </div>
            <div class="flex justify-between">
            <span>Total Rounds</span>
            <span class="font-bold">45</span>
            </div>
        </div>

        <!-- Round Totals -->
        <div class="mt-6 border-t pt-4 space-y-2 text-sm">
            <p class="font-semibold mb-2">Current Round Totals</p>

            <div class="flex justify-between">
            <span class="text-red-600 font-medium">MERON (2.475)</span>
            <span class="font-bold">₱2,000</span>
            </div>

            <div class="flex justify-between">
            <span class="text-yellow-600 font-medium">DRAW (1.7)</span>
            <span class="font-bold">₱500</span>
            </div>

            <div class="flex justify-between">
            <span class="text-green-600 font-medium">WALA (1.65)</span>
            <span class="font-bold">₱3,000</span>
            </div>

            <div class="flex justify-between border-t pt-2 font-semibold">
            <span>Total</span>
            <span>₱5,500</span>
            </div>
        </div>

        <!-- Logs -->
        <div class="mt-6 border-t pt-4"><p class="font-semibold mb-3">Recent Rounds <span class="float-right">Winner</span></p></div>
        <div class="flex-1 overflow-y-auto max-h-96">
            <ul class="space-y-2 text-sm">
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#11</span>
                <span class="text-green-600 font-bold">WALA</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#10</span>
                <span class="text-red-600 font-bold">MERON</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#9</span>
                <span class="text-yellow-600 font-bold">DRAW</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#8</span>
                <span class="text-yellow-600 font-bold">DRAW</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#7</span>
                <span class="text-yellow-600 font-bold">DRAW</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#6</span>
                <span class="text-green-600 font-bold">WALA</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#5</span>
                <span class="text-red-600 font-bold">MERON</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#4</span>
                <span class="text-green-600 font-bold">WALA</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#3</span>
                <span class="text-red-600 font-bold">MERON</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#2</span>
                <span class="text-green-600 font-bold">WALA</span>
            </li>
            <li class="flex justify-between bg-gray-50 p-2 rounded-lg">
                <span>#1</span>
                <span class="text-red-600 font-bold">MERON</span>
            </li>
            </ul>
        </div>

        </div>

    </div>
    </div>
</template>
<style>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>