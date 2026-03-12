<script setup lang="ts">
    import Echo from 'laravel-echo'
    import { computed, ref, onMounted, onUnmounted } from 'vue'
    import Toast from '@/components/Toast.vue'
    import { formatDate, tellerClock } from '@/helpers/time';
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

    function updateRoundState(round: any, reason: string | null = null, skip: boolean) {
        currentRound.value = round
        if (skip) return

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
            '.round.opened': e => updateRoundState(e.round, '', true),
            '.round.closed': e => updateRoundState(e.round, '', true),
            '.round.cancelled': e => updateRoundState(e.round, e.reason, false),
            '.round.declare': e => {
                updateRoundState(e.round, '', false)
                addToast(
                    `Winner for round #${e.round.round_number} is ${e.round?.winner.toUpperCase()}`,
                    'success'
                )
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
                <div class="text-2xl mt-2 font-bold opacity-90">1.85</div>
            </div>
            </div>

            <div class="text-center mt-8 space-y-2">
            <div class="text-2xl md:text-3xl bg-red-700/50 py-1 rounded">1-0-0-1</div>
            <div class="text-4xl md:text-5xl font-black">117,933</div>
            <div class="bg-red-800/40 p-4 rounded-b-lg border-t border-red-400/30">
                <div class="text-xl font-bold">PAYOUT</div>
                <div class="text-3xl md:text-4xl font-bold">183.29</div>
            </div>
            </div>
        </div>

        <div class="order-2 lg:col-span-2 bg-yellow-400 text-black rounded-lg p-6 flex flex-col justify-center items-center min-h-37.5 lg:min-h-full">
            <div class="text-3xl lg:text-4xl font-bold text-yellow-900">DRAW</div>
            <div class="text-4xl font-black mt-2">800</div>
            <div class="mt-4 text-2xl bg-yellow-500/30 py-2 w-full text-center font-bold rounded">1 - 7</div>
        </div>

        <div class="order-3 lg:col-span-4 bg-blue-600 rounded-lg p-4 md:p-6 flex flex-col justify-between min-h-75 lg:min-h-112.5">
            <div>
            <div class="text-4xl font-black text-center">WALA</div>
            <div class="mt-6 text-center">
                <div class="text-3xl md:text-4xl font-black uppercase">TEAM WALA</div>
                <div class="text-2xl mt-2 font-bold opacity-90">1.95</div>
            </div>
            </div>

            <div class="text-center mt-8 space-y-2">
            <div class="text-2xl md:text-3xl bg-blue-700/50 py-1 rounded">0-0-1-1</div>
            <div class="text-4xl md:text-5xl font-black">110,094</div>
            <div class="bg-blue-800/40 p-4 rounded-b-lg border-t border-blue-400/30">
                <div class="text-xl font-bold">PAYOUT</div>
                <div class="text-3xl md:text-4xl font-bold">196.35</div>
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