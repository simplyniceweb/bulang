<script setup lang="ts">
    import { Head, router } from '@inertiajs/vue3'
    import Echo from 'laravel-echo'
    import { CircleUserRound } from 'lucide-vue-next'
    import { computed, ref, onMounted, onUnmounted } from 'vue'
    import ConfirmModal from '@/components/ConfirmModal.vue'
    import Toast from '@/components/Toast.vue'
    import { addToast } from '@/helpers/toast'
    import { route } from 'ziggy-js';
    import BetConfirm from './BetConfirm.vue'

    let echo = null as Echo<any> | null
    const cancellationReason = ref(null)
    const confirmModal = ref(false)
    const confirmMessage = ref('')
    const confirmAction = ref<() => void>(() => {})
    const confirmType = ref('default')
    type Side = 'meron' | 'wala' | 'draw'

    const props = defineProps<{
        event: any
        round: any,
        rounds: any[]
    }>()

    interface Round {
        id: number
        round_number: number
        status: string
        winner?: Side | null
        meron_closed: boolean
        wala_closed: boolean
        draw_closed: boolean
    }

    const currentRound = ref<Round | null>(props.round)
    const rounds = ref<Round[]>([...props.rounds])
    const meronClosed = computed(() => currentRound.value?.meron_closed ?? false)
    const walaClosed = computed(() => currentRound.value?.wala_closed ?? false)
    const drawClosed = computed(() => currentRound.value?.draw_closed ?? false)
    const roundStatus = computed(() => currentRound.value?.status)
    const roundNumber = computed(() => currentRound.value?.round_number)
    const remainingBalance = computed(() => balance.value - betAmount.value)

    onMounted(() => {
        initializeEcho()
    })

    onUnmounted(() => {
        echo?.leave('rounds')
    })

    // bet amount
    const betAmount = ref(0);
    const presets = [100,200,300,400,500,600,700,800,900,1000]

    const setAmount = (value: number) => {
        betAmount.value = value
    }

    const clearAmount = () => betAmount.value = 0

    // bet confirmation
    const side = ref<'meron' | 'wala' | 'draw'>('meron')
    const capital = 10000;
    const balance = ref(capital);
    const showConfirm = ref(false)

    const cancelBet = () => showConfirm.value = false

    const askLogout = () => {
        confirmMessage.value = 'You will be logged out of the system. Continue?'
        confirmType.value = 'danger'

        confirmAction.value = () => {
            router.post(
                route('logout'), 
                {},
                {
                    onSuccess: () => {
                        addToast('Logged out successfully.', 'success');
                        router.flushAll()
                    }
                }
            )
        }

        confirmModal.value = true
    }

    function updateRoundState(round: any, reason: string | null = null) {
        currentRound.value = round

        const index = rounds.value.findIndex(r => r.id === round.id)

        if (index !== -1) {
            rounds.value[index] = round
        } else {
            rounds.value.unshift(round)
        }

        cancellationReason.value = reason
    }

    function initializeEcho() {
        echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        })

        echo.connector.pusher.connection.bind('connected', () => {
            console.log('Reverb connected')
        })

        const events: Record<string, (event: any) => void> = {
            '.round.opened': e => updateRoundState(e.round),

            '.round.closed': e => updateRoundState(e.round),

            '.round.cancelled': e => updateRoundState(e.round, e.reason),

            '.round.declare': e => {
                updateRoundState(e.round)
                addToast(
                    `Winner for round #${e.round.round_number} is ${e.round?.winner.toUpperCase()}`,
                    'success'
                )
            },

            '.round.side.closed': e => {
                updateRoundState(e.round)
                addToast(`${e.side.toUpperCase()} betting closed`, 'error')
            },

            '.round.side.reopened': e => {
                updateRoundState(e.round)
                addToast(`${e.side.toUpperCase()} betting reopened`, 'success')
            }
        }

        const channel = echo.channel('rounds')

        Object.entries(events).forEach(([event, handler]) => {
            channel.listen(event, handler)
        })
    }

    function placeBet(selectedSide: Side) {

        if (roundStatus.value !== 'open') {
            addToast('Betting is currently closed', 'error')
            return
        }

        if (isSideClosed(selectedSide)) {
            addToast(`${selectedSide.toUpperCase()} betting is closed`, 'error')
            return
        }

        side.value = selectedSide
        showConfirm.value = true
    }

    function confirmBet() {

        if (betAmount.value <= 0) {
            addToast('Enter a valid bet amount.', 'error')
            return
        }

        if (betAmount.value > balance.value) {
            addToast('Insufficient balance.', 'error')
            return
        }

        balance.value -= betAmount.value

        addToast(
            `Bet ₱${betAmount.value} on ${side.value.toUpperCase()} confirmed`,
            'success'
        )

        betAmount.value = 0
        showConfirm.value = false
    }

    function isSideClosed(side: Side): boolean {
        return currentRound.value?.[`${side}_closed`] ?? false
    }
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
                <p class="text-gray-800 text-sm">Current Round</p>
                <p class="text-4xl font-extrabold text-indigo-600">
                    <span :class="roundNumber ? 'text-indigo-600' : 'text-gray-300'">
                        # {{ roundNumber ?? '---' }}
                    </span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-gray-800 text-sm">Status</p>
                <p
                :class="[
                'text-4xl uppercase font-bold',
                    {
                        'text-green-500': roundStatus === 'open',
                        'text-red-500': roundStatus === 'closed',
                        'text-gray-500': roundStatus === 'cancelled',
                        'text-gray-300': !roundStatus
                    }
                ]"
                >
                    {{ roundStatus ?? '---' }}
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
                        'font-bold md:font-semibold py-6 md:py-8 rounded-xl cursor-pointer text-md md:text-3xl transition',
                        betAmount === preset
                        ? 'bg-indigo-600 text-white'
                        : 'bg-indigo-200 hover:bg-indigo-300'
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
                :disabled="walaClosed"
                :class="[
                    'text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg transition transform duration-150 w-full',
                    !walaClosed 
                    ? 'bg-blue-600 hover:bg-blue-700 active:scale-95 cursor-pointer' 
                    : 'bg-blue-400 cursor-not-allowed opacity-50'
                ]">
                <span>WALA</span>
                <span v-if="walaClosed" class="block text-xs mt-1">(CLOSED)</span>
            </button>

            <button
                @click="placeBet('draw')"
                :disabled="drawClosed"
                :class="[
                    'text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg transition transform duration-150 w-full',
                    !drawClosed 
                    ? 'bg-yellow-600 hover:bg-yellow-700 active:scale-95 cursor-pointer' 
                    : 'bg-yellow-400 cursor-not-allowed opacity-50'
                ]"
                >
                <span>DRAW</span>
                <span v-if="drawClosed" class="block text-xs mt-1">(CLOSED)</span>
            </button>

            <button
                @click="placeBet('meron')"
                :disabled="meronClosed"
                :class="[
                    'text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg transition transform duration-150 w-full',
                    !meronClosed 
                    ? 'bg-red-600 hover:bg-red-700 active:scale-95 cursor-pointer' 
                    : 'bg-red-400 cursor-not-allowed opacity-50'
                ]"
                >
                <span>MERON</span>
                <span v-if="meronClosed" class="block text-xs mt-1">(CLOSED)</span>
            </button>
            <BetConfirm
                :visible="showConfirm"
                :amount="betAmount"
                :side="side"
                :remaining-balance="remainingBalance"
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
            <button
                class="text-sm text-red-500 hover:underline cursor-pointer"
                @click.prevent="askLogout"
                data-test="logout-button"
            >
                Log out
            </button>
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
            <span class="font-bold">{{ roundNumber ?? '---' }}</span>
            </div>
        </div>

        <!-- Round Totals -->
        <div class="mt-6 border-t pt-4 space-y-2 text-sm">
            <p class="font-semibold mb-2">Current Round Totals</p>

            <div class="flex justify-between">
                <span class="text-red-600 font-medium">MERON (2.475)</span>
                <span class="font-bold">₱2,000</span>
                <span v-if="meronClosed" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
            </div>

            <div class="flex justify-between">
                <span class="text-yellow-600 font-medium">DRAW (1.7)</span>
                <span class="font-bold">₱500</span>
                <span v-if="drawClosed" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
            </div>

            <div class="flex justify-between">
                <span class="text-green-600 font-medium">WALA (1.65)</span>
                <span class="font-bold">₱3,000</span>
                <span v-if="walaClosed" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
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
                <li v-for="item in rounds" :key="item.id" class="flex justify-between bg-gray-50 p-2 rounded-lg">
                    <span>#{{ item.round_number }}</span>
                    <span :class="{'text-indigo-600': item.winner==='wala','text-red-600': item.winner==='meron','text-yellow-500': item.winner==='draw','text-gray-500': !item.winner}" class="font-bold">
                        {{ item.winner ? item.winner?.toUpperCase() : '---' }} / {{ item.status.toUpperCase() }}
                    </span>
                </li>
            </ul>
        </div>

        </div>


        <ConfirmModal
            :show="confirmModal"
            title="Confirm Action"
            :message="confirmMessage"
            confirmText="Confirm"
            cancelText="Cancel"
            @confirm="() => { confirmAction(); confirmModal = false }"
            @cancel="confirmModal = false"
            :colorType="confirmType"
        />
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