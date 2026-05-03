<script setup lang="ts">
    import { router } from '@inertiajs/vue3'
    import Echo from 'laravel-echo'
    import { CircleUserRound } from 'lucide-vue-next'
    import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
    import AlertModal from '@/components/AlertModal.vue'
    import ConfirmModal from '@/components/ConfirmModal.vue'
    import StaticAlertModal from '@/components/StaticAlertModal.vue'
    import Toast from '@/components/Toast.vue'
    import { useAlert } from '@/composables/useAlert'
    import { useFlashAlert } from '@/composables/useFlashAlert'
    import { formatNumber } from '@/helpers/format'
    import { addToast } from '@/helpers/toast'
    import { route } from 'ziggy-js'

    const confirmModal = ref(false)
    const confirmMessage = ref('')
    const confirmAction = ref<() => void>(() => {})
    const confirmType = ref('default')
    const declaredWinner = ref<"wala" | "meron" | "draw" | null>(null);
    const isBlinking = computed(() => declaredWinner.value !== null);

    const { showAlert } = useAlert()

    const {
        show,
        message,
        type,
        close
    } = useFlashAlert()

    const props = defineProps<{
        event: any
        round: any,
        rounds: any[],
    }>()

    let echo = null as Echo<any> | null

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
            '.round.opened': e => {
                console.log(e.round.id + ' is the new round.')
                sumStats.value = { ...initialStats };
            },
            '.round.bet_placed': e => {
                console.log(e.round + ' bet placed.')
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

    // const housePercent = ref(props.event?.house_percent ?? 6);

    // Priority: Active Round -> Event Default -> Hardcoded 6
    const housePercent = ref(
        props.round?.house_percent ?? 
        props.event?.house_percent ?? 
        6
    );

    // Optional: If you want the input to update automatically 
    // when the round changes (e.g., via Inertia reload)
    watch(() => props.round?.house_percent, (newVal) => {
        if (newVal !== undefined && newVal !== null) {
            housePercent.value = newVal;
        }
    });

    function startRound() {
        confirmAndPost(
            `Open round #${(props.round?.round_number || 0) + 1} with ${housePercent.value}% house take?`,
            'game_master.round.open',
            { house_percent: housePercent.value },
            'success',
            'success'
        )
    }

    function cancelRound() {
        if (!requireRound()) return;

        confirmAndPost(
            'Cancel current round? This will invalidate all bets placed.',
            'game_master.round.cancel'
        )
    }

    function closeSide(side: 'wala' | 'meron' | 'draw', isClosed?: boolean) {
        if (!requireRound()) return

        confirmAndPost(
            isClosed
                ? `${side.toUpperCase()} betting is already closed. Reopen it?`
                : `Close ${side.toUpperCase()} betting?`,
            'game_master.round.closeSide',
            { side, reopen: isClosed },
            isClosed ? 'success' : 'error',
            side
        )
    }

    function closeGlobalBetting() {
        if (!requireRound()) return

        confirmAndPost(
            'Close ALL betting? No further bets will be allowed.',
            'game_master.round.closeGlobalBetting',
            {},
            'success',
            'danger'
        )
    }

    function confirmWinner(side: 'wala' | 'meron' | 'draw') {
        if (!requireRound()) return

        if (isBlinking.value) return;

        confirmAndPost(
            `Declare winner: ${side.toUpperCase()}?`,
            'game_master.round.declare',
            { winner: side },
            'success',
            side
        )

        declaredWinner.value = side;

        setTimeout(() => {
            declaredWinner.value = null;
        }, 10000); 
    }

    function askLogout() {
        confirmMessage.value = 'You will be logged out. Continue?'
        confirmType.value = 'danger'

        confirmAction.value = () => {
            router.post(route('logout'), {}, {
                onSuccess: () => {
                    addToast('Logged out successfully.', 'success')
                    router.flushAll()
                }
            })
        }

        confirmModal.value = true
    }

    function confirmAndPost(
        message: string,
        routeName: string,
        payload: Record<string, any> = {},
        toastType: 'success' | 'error' = 'success',
        confirmColor: string = 'default'
    ) {
        confirmMessage.value = message
        confirmType.value = confirmColor

        confirmAction.value = () => {
            router.post(
                route(routeName, props.round?.id),
                { ...payload, noModal: true },
                {
                    onSuccess: (page: any) => {
                        if (page.props.flash?.success) {
                            addToast(page.props.flash?.success as string, 'success');
                        } else {
                            addToast(page.props.flash?.error as string, 'error');
                        }
                    },
                }
            )
        }

        confirmModal.value = true
    }

    function requireRound(): boolean {
        if (!props.round.id) {
            showAlert('No active round.')
            return false
        }
        return true
    }

    const isHalted = ref(props.event.halt_event);

    watch(() => props.event.halt_event, (newVal) => {
        isHalted.value = newVal;
    });

    function toggleHalt() {
        const actionText = isHalted.value ? 'RESUME' : 'HALT';
        
        confirmAndPost(
            `EMERGENCY: ${actionText} all processes for this event?`,
            'game_master.event.halt',
            { halt: !isHalted.value },
            isHalted.value ? 'success' : 'error',
            'danger'
        )
    }
</script>

<template>
    <Toast />
    <div class="p-4 grid grid-cols-1 lg:grid-cols-3 gap-6 h-screen items-start">
        
        <!-- LEFT PANEL: Round Controls -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col gap-6">
            
            <div class="flex flex-col gap-4 border-b pb-6">
                <h1 class="text-4xl font-bold text-black">{{ props.event.name ?? 'Test Event' }}</h1>
                <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Current Round: #{{ props.round ? props.round?.round_number : '---' }}</h2>
                <p class="font-semibold">Status: 
                    <span :class="props.round?.status==='open'?'text-green-600':'text-red-600'">{{ props.round?.status.toUpperCase() }}</span>
                </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="startRound"
                        class="flex-1 bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 transition shadow-lg flex flex-col items-center justify-center leading-tight"
                    >
                        <span class="text-lg">OPEN NEW ROUND</span>
                        <span class="text-xs opacity-80">SET TO {{ housePercent }}%</span>
                    </button>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">House Take %</label>
                        <input 
                            v-model="housePercent" 
                            type="number" 
                            step="0.1"
                            class="w-24 bg-gray-100 border-2 border-transparent focus:border-green-500 focus:bg-white rounded-xl px-3 py-4 font-black text-center outline-none transition"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="flex flex-col gap-4 border-r pr-0 md:pr-8">
                <h3 class="font-bold text-gray-700 uppercase text-sm tracking-wider">Declare Result</h3>
                <div class="flex flex-col gap-3">
                    <button
                        @click="confirmWinner('wala')"
                        :disabled="isBlinking"
                        :class="[
                            'w-full py-3 rounded-lg text-white font-semibold transition bg-indigo-600 hover:bg-indigo-700',
                            declaredWinner === 'wala' ? 'active-blink ring-4 ring-indigo-300' : (isBlinking ? 'opacity-50' : '')
                        ]"
                    >
                        WINNER WALA
                    </button>

                    <button
                        @click="confirmWinner('meron')"
                        :disabled="isBlinking"
                        :class="[
                            'w-full py-3 rounded-lg text-white font-semibold transition bg-red-600 hover:bg-red-700',
                            declaredWinner === 'meron' ? 'active-blink ring-4 ring-red-300' : (isBlinking ? 'opacity-50' : '')
                        ]"
                    >
                        WINNER MERON
                    </button>

                    <button
                        @click="confirmWinner('draw')"
                        :disabled="isBlinking"
                        :class="[
                            'w-full py-3 rounded-lg text-white font-semibold transition bg-yellow-600 hover:bg-yellow-700',
                            declaredWinner === 'draw' ? 'active-blink ring-4 ring-yellow-300' : (isBlinking ? 'opacity-50' : '')
                        ]"
                    >
                        WINNER DRAW
                    </button>
                </div>

                <!-- Optional: Status message -->
                <p v-if="isBlinking" class="text-center text-xs mt-2 font-bold animate-pulse text-gray-500">
                    WINNER DECLARED: PROCESSING...
                </p>

                <div class="mt-4 bg-gray-50 p-4 rounded-xl grid grid-cols-3 text-center font-bold">
                    <div class="text-indigo-600 text-sm">
                        WALA<br>
                        <span class="text-sm">({{ sumStats.wala_payout > 0 ? sumStats.wala_payout : '---' }})</span><br>
                        <span class="text-lg">{{ sumStats.wala_total > 0 ? formatNumber(sumStats.wala_total, 0) : '---'}}</span>
                    </div>
                    <div class="text-yellow-600 text-sm">
                        DRAW<br>
                        <span class="text-sm">(1-7)</span><br>
                        <span class="text-lg">{{ sumStats.draw_total > 0 ? formatNumber(sumStats.draw_total, 0) : '---'}}</span>
                    </div>
                    <div class="text-red-600 text-sm">
                        MERON<br>
                        <span class="text-sm">({{ sumStats.meron_payout > 0 ? sumStats.meron_payout : '---' }})</span><br>
                        <span class="text-lg">{{ sumStats.meron_total > 0 ? formatNumber(sumStats.meron_total, 0) : '---'}}</span>
                    </div>
                </div>

                <button
                    @click="toggleHalt"
                    class="w-full py-3 rounded-lg font-black transition shadow-sm border-2 mt-3"
                    :class="isHalted 
                        ? 'bg-green-100 border-green-600 text-green-700 hover:bg-green-200' 
                        : 'bg-red-600 border-red-700 text-white hover:bg-red-800 animate-pulse'"
                >
                    ⚠ {{ isHalted ? 'RESUME ALL PROCESSES' : 'EMERGENCY STOP' }} ⚠
                </button>
                </div>

                <div class="flex flex-col gap-4">
                <h3 class="font-bold text-gray-700 uppercase text-sm tracking-wider">Betting Controls</h3>
                <div class="flex flex-col gap-3">
                    <button
                    @click="closeSide('wala', props.round?.wala_closed)"
                    class="w-full py-3 rounded-lg font-semibold transition"
                    :class="props.round?.wala_closed ? 'bg-indigo-300 text-black' : 'bg-indigo-600 hover:bg-indigo-700 text-white'"
                    >
                    {{ props.round?.wala_closed ? 'REOPEN WALA' : 'CLOSE WALA' }}
                    </button>

                    <button
                    @click="closeSide('draw', props.round?.draw_closed)"
                    class="w-full py-3 rounded-lg font-semibold transition"
                    :class="props.round?.draw_closed ? 'bg-yellow-300 text-black' : 'bg-yellow-600 hover:bg-yellow-700 text-white'"
                    >
                    {{ props.round?.draw_closed ? 'REOPEN DRAW' : 'CLOSE DRAW' }}
                    </button>

                    <button
                    @click="closeSide('meron', props.round?.meron_closed)"
                    class="w-full py-3 rounded-lg font-semibold transition"
                    :class="props.round?.meron_closed ? 'bg-red-300 text-black' : 'bg-red-600 hover:bg-red-700 text-white'"
                    >
                    {{ props.round?.meron_closed ? 'REOPEN MERON' : 'CLOSE MERON' }}
                    </button>

                    <div class="mt-auto pt-4">
                    <button
                    @click="closeGlobalBetting()"
                    class="mb-3 w-full bg-gray-200 text-gray-600 py-2 rounded-lg font-bold hover:bg-red-100 hover:text-red-600 transition"
                    >
                    CLOSE GLOBAL BETTING
                    </button>
                    <button
                        @click="cancelRound"
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
                <button
                    class="text-sm text-red-500 hover:underline cursor-pointer"
                    @click.prevent="askLogout"
                    data-test="logout-button"
                >
                    Log out
                </button>
            </div>

        <h3 class="text-lg font-bold my-3">Recent Rounds</h3>
        <div class="flex-1 overflow-y-auto space-y-4">
            <div v-for="item in rounds" :key="item.id" class="border rounded-lg p-3 hover:bg-gray-50">
            <div class="flex justify-between items-center mb-2">
                <span class="font-bold">Round #{{ item.round_number }}</span>
                <span 
                    :class="{
                        'text-red-600': item.winner === 'meron',
                        'text-indigo-600': item.winner === 'wala',
                        'text-yellow-500': item.winner === 'draw',
                        'text-green-500': !item.winner && item.status === 'open',
                        'text-orange-500': !item.winner && item.status === 'cancelled',
                        'text-gray-500': !item.winner && item.status !== 'open' && item.status !== 'cancelled'
                    }" 
                    class="font-bold"
                >
                    {{ (item.winner || item.status).toUpperCase() }}
                </span>
            </div>
            </div>
        </div>
        </div>

        <AlertModal
            :show="show"
            :message="message"
            :type="type"
            @close="close"
        />

        <StaticAlertModal />

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
</template>

<style scoped>
.active-blink {
    animation: blink-bg 1s step-start infinite;
}

@keyframes blink-bg {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>