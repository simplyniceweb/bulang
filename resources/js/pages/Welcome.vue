<script setup lang="ts">
    import Echo from 'laravel-echo'
    import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
    import Toast from '@/components/Toast.vue'
    import { formatNumber } from '@/helpers/format'
    import { formatDate, tellerClock } from '@/helpers/time'
    import { addToast } from '@/helpers/toast'

    type Side = 'meron' | 'wala' | 'draw'

    const colors: Record<string, string> = {
        'wala': 'text-cyan-300',
        'meron': 'text-pink-400',
        'cancelled': 'text-gray-300',
        'open': 'text-green-400',
        'draw': 'text-yellow-400',
        'default': 'text-white' // The fallback class
    };

    const props = defineProps<{
        event: any
        round: any,
        rounds: any[],
        stats: {
            meron_count: number;
            wala_count: number;
            draw_count: number;
            cancelled_count: number;
        }
    }>()

    interface Round {
        id: number
        event_id: number
        round_number: number
        status: string
        winner?: Side | null
        meron_closed: boolean
        wala_closed: boolean
        draw_closed: boolean
    }

    let echo = null as Echo<any> | null
    const currentRound = ref<Round | null>(props.round)
    const rounds = ref<Round[]>([...props.rounds])
    const roundStatus = computed(() => currentRound.value?.status)
    const roundNumber = computed(() => currentRound.value?.round_number)
    const cancellationReason = ref<string | null>(null)
    const localStats = ref({ ...props.stats });
    const showWinnerPopup = ref(false);

    const initialStats = {
        meron_total: 0,
        wala_total: 0,
        draw_total: 0,
        meron_payout: 0,
        wala_payout: 0,
        draw_multiplier: 7 // Keep your default multiplier
    };

    const sumStats = ref(props.round?.payout_details || {...initialStats});

    watch(() => props.round?.payout_details, (newVal) => {
        if (newVal) sumStats.value = newVal;
    }, { deep: true });

    function updateRoundState(round: any, reason: string | null = null, skip: boolean) {
        currentRound.value = round
        if (skip) {
            if (reason === 'reset') {
                sumStats.value = { ...initialStats };
            }
            return
        }

        const index = rounds.value.findIndex(r => r.id === round.id)

        const isAlreadyProcessed = index !== -1 && (rounds.value[index].winner || rounds.value[index].status === 'cancelled');

        // LOGIC: If the round is updated with a winner or cancelled status, update localStats
        if (!isAlreadyProcessed) {
            if (round.status === 'cancelled') {
                localStats.value.cancelled_count++;
            } else if (round.winner) {
                if (round.winner === 'meron') localStats.value.meron_count++;
                if (round.winner === 'wala') localStats.value.wala_count++;
                if (round.winner === 'draw') localStats.value.draw_count++;
            }
        }

        if (index !== -1) {
            rounds.value[index] = round
        } else {
            rounds.value.unshift(round)
            
            if (rounds.value.length > 4) {
                rounds.value = rounds.value.slice(0, 4);
            }
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
            '.round.opened': e => updateRoundState(e.round, 'reset', true),
            '.round.closed': e => updateRoundState(e.round, '', true),
            '.round.cancelled': e => updateRoundState(e.round, e.reason, false),
            '.round.declare': e => {
                updateRoundState(e.round, 'declare', false)
                addToast(
                    `Winner for round #${e.round.round_number} is ${e.round?.winner.toUpperCase()}`,
                    'success'
                )

                showWinnerPopup.value = true;
                
                // Optional: Auto-hide after 10 seconds
                setTimeout(() => {
                    showWinnerPopup.value = false;
                }, 10000);
            },
            '.round.bet_placed': e => {
                console.log(e.round.round_id + ' bet placed.')
                sumStats.value = e.payouts;
            }
        }

        const channel = echo.channel('rounds')

        Object.entries(events).forEach(([event, handler]) => {
            channel.listen(event, handler)
        })
    }

    onMounted(() => {
        initializeEcho()
    })

    onUnmounted(() => {
        echo?.leave('rounds')
    })

    // date time
    const currentTime = ref('');

    function updateClock() {
        const current = tellerClock(new Date(), true);
        currentTime.value = current.time;
    }

    updateClock()
    setInterval(updateClock, 1000);
</script>

<template>
    <Toast />
    <div class="min-h-screen bg-blue-900 text-white p-4 md:p-6">
        <div>
            <h1 class="text-4xl font-black">Round # {{ roundNumber ?? '---' }}</h1>
        </div>

        <transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            >
                <div v-if="showWinnerPopup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
                    <div class="relative bg-gray-900 border-2 border-yellow-500 rounded-2xl p-8 shadow-[0_0_50px_rgba(234,179,8,0.3)] text-center max-w-sm w-full mx-4">
                    
                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 bg-yellow-500 rounded-full p-4 shadow-lg animate-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14" />
                        </svg>
                    </div>

                    <div class="mt-6">
                        <h3 class="text-gray-400 uppercase tracking-widest text-sm font-bold">Round #{{ currentRound?.round_number }} Result</h3>
                        
                        <div 
                        :class="{
                            'text-red-500': currentRound?.winner === 'meron',
                            'text-indigo-500': currentRound?.winner === 'wala',
                            'text-yellow-500': currentRound?.winner === 'draw'
                        }"
                        class="text-6xl font-black tracking-tighter my-4 drop-shadow-md"
                        >
                        {{ currentRound?.winner ? currentRound?.winner.toUpperCase() : '---' }}
                        </div>
                        
                        <p class="text-white text-lg font-medium opacity-80">WINNER DECLARED</p>
                    </div>

                    <button 
                        @click="showWinnerPopup = false" 
                        class="mt-8 w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl transition"
                    >
                        CLOSE
                    </button>
                    </div>
                </div>
        </transition>

        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="text-xl md:text-2xl font-bold tracking-wide text-center md:text-left">
            GALERA DE NATO [ {{ props.event?.name }} ] | {{ formatDate(new Date()) }}
        </div>

        <div class="flex flex-col items-center md:items-end">
            <div class="text-xl font-bold">{{ currentTime }}</div>
            <div 
                :class="[
                    'px-4 py-1 rounded text-lg font-bold mt-1 animate-pulse',
                    {
                        'bg-green-500': roundStatus === 'open',
                        'bg-red-500': roundStatus === 'closed',
                        'bg-gray-500': roundStatus === 'cancelled',
                        'bg-gray-300': !roundStatus
                    }
                ]">
            BETTING {{ roundStatus?.toUpperCase() ?? '---' }}
            </div>
        </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        <div class="order-1 lg:col-span-4 bg-red-600 rounded-lg p-4 md:p-6 flex flex-col justify-between min-h-75 lg:min-h-112.5">
            <div>
            <div class="text-4xl font-black text-center">MERON</div>
            <div class="mt-6 text-center">
                <div class="text-3xl md:text-4xl font-black uppercase">TEAM MERON</div>
                <div class="text-2xl mt-2 font-bold opacity-90">1.2</div>
            </div>
            </div>

            <div class="text-center mt-8 space-y-2">
            <div class="text-2xl md:text-3xl bg-red-700/50 py-1 rounded">1-0-0-1</div>
            <div class="text-4xl md:text-5xl font-black">₱ {{ formatNumber(sumStats.meron_total, 0) }}</div>
            <div class="bg-red-800/40 p-4 rounded-b-lg border-t border-red-400/30">
                <div class="text-xl font-bold">PAYOUT</div>
                <div class="text-3xl md:text-4xl font-bold">{{ sumStats.meron_payout }}</div>
            </div>
            </div>
        </div>

        <div class="order-2 lg:col-span-2 bg-yellow-400 text-black rounded-lg p-6 flex flex-col justify-center items-center min-h-37.5 lg:min-h-full">
            <div class="text-3xl lg:text-4xl font-bold text-yellow-900">DRAW</div>
            <div class="text-4xl font-black mt-2">₱ {{ formatNumber(sumStats.draw_total, 0) }}</div>
            <div class="mt-4 text-2xl bg-yellow-500/30 py-2 w-full text-center font-bold rounded">1 - 7</div>
        </div>

        <div class="order-3 lg:col-span-4 bg-blue-600 rounded-lg p-4 md:p-6 flex flex-col justify-between min-h-75 lg:min-h-112.5">
            <div>
            <div class="text-4xl font-black text-center">WALA</div>
            <div class="mt-6 text-center">
                <div class="text-3xl md:text-4xl font-black uppercase">TEAM WALA</div>
                <div class="text-2xl mt-2 font-bold opacity-90">1.3</div>
            </div>
            </div>

            <div class="text-center mt-8 space-y-2">
            <div class="text-2xl md:text-3xl bg-blue-700/50 py-1 rounded">0-0-1-1</div>
            <div class="text-4xl md:text-5xl font-black">₱ {{ formatNumber(sumStats.wala_total, 0) }}</div>
            <div class="bg-blue-800/40 p-4 rounded-b-lg border-t border-blue-400/30">
                <div class="text-xl font-bold">PAYOUT</div>
                <div class="text-3xl md:text-4xl font-bold">{{ sumStats.wala_payout }}</div>
            </div>
            </div>
        </div>

        <div class="order-4 lg:order-first lg:col-span-2 bg-blue-800/50 backdrop-blur-sm rounded-lg p-4 space-y-6">
            <div>
            <div class="text-lg font-bold mb-3 border-b border-blue-600 pb-1">SUMMARY</div>
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-1 text-sm md:text-base">
                <div class="flex justify-between font-bold bg-red-600 p-2 rounded-s lg:rounded-none">
                <span>MERON</span><span>{{ localStats.meron_count }}</span>
                </div>
                <div class="flex justify-between font-bold bg-blue-600 p-2 rounded-e lg:rounded-none">
                <span>WALA</span><span>{{ localStats.wala_count }}</span>
                </div>
                <div class="flex justify-between font-bold bg-yellow-500 p-2 text-black rounded-s lg:rounded-none">
                <span>DRAW</span><span>{{ localStats.draw_count }}</span>
                </div>
                <div class="flex justify-between font-bold bg-gray-600 p-2 rounded-e lg:rounded-none">
                <span>CANCEL</span><span>{{ localStats.cancelled_count }}</span>
                </div>
            </div>
            </div>

            <div class="pt-2">
            <div class="text-lg font-bold mb-2 flex justify-between items-center">
                <span>HISTORY</span>
                <span class="text-xs opacity-60 uppercase">Winner</span>
            </div>
            <div class="space-y-2 max-h-48 lg:max-h-none overflow-y-auto pr-2">
                <div  v-for="item in rounds" :key="item.id" class="flex justify-between items-center py-1 border-b border-blue-700/50 font-bold">
                <span class="text-blue-300">#{{ item.round_number }}</span>
                    <span :class="colors[item.winner?.toLowerCase() ?? 'default']">
                    {{ item.winner?.toUpperCase() || item.status?.toUpperCase() }}
                    </span>
                </div>
            </div>
            </div>
        </div>

        </div>
    </div>
</template>