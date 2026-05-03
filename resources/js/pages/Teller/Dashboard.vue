<script setup lang="ts">
    import { Head, router, usePage } from '@inertiajs/vue3'
    import axios from 'axios';
    import Echo from 'laravel-echo'
    import { CircleUserRound } from 'lucide-vue-next'
    import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
    import ConfirmModal from '@/components/ConfirmModal.vue'
    import TicketModal from '@/components/TicketModal.vue'
    import Toast from '@/components/Toast.vue'
    import { formatNumber } from '@/helpers/format'
    import { addToast } from '@/helpers/toast'
    import { route } from 'ziggy-js';
    import BetConfirm from './BetConfirm.vue'
    import PayoutTicket from './PayoutTicket.vue'
    import SupervisorTransaction from './SupervisorTransaction.vue'
    import TicketReceipt from './Ticket.vue'

    let echo = null as Echo<any> | null
    const cancellationReason = ref<string | null>(null)
    const confirmModal = ref(false)
    const confirmMessage = ref('')
    const confirmAction = ref<() => void>(() => {})
    const confirmType = ref('default')
    type Side = 'meron' | 'wala' | 'draw'

    const props = defineProps<{
        event: any
        round: any,
        rounds: any[],
        tickets: any[],
        teller: {
            id: number,
            name: string,
            initial: number,
            current: number
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

    interface Ticket {
        id: number
        ticket_number: string
        amount: number
        status: string
    }

    const currentRound = ref<Round | null>(props.round)
    const rounds = ref<Round[]>([...props.rounds])
    const tickets = ref<Ticket[]>([...props.tickets])
    const tellerWallet = ref(props.teller);
    const meronClosed = computed(() => currentRound.value?.meron_closed ?? false)
    const walaClosed = computed(() => currentRound.value?.wala_closed ?? false)
    const drawClosed = computed(() => currentRound.value?.draw_closed ?? false)
    const roundStatus = computed(() => currentRound.value?.status)
    const roundNumber = computed(() => currentRound.value?.round_number)

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

    // bet amount
    const betAmount = ref(0);
    const presets = [100,200,300,400,500,600,700,800,900,1000]

    const setAmount = (value: number) => {
        betAmount.value = value
    }

    const clearAmount = () => betAmount.value = 0

    // bet confirmation
    const side = ref<'meron' | 'wala' | 'draw'>('meron')
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

        if (reason === 'reset') {
            sumStats.value = { ...initialStats };
            addToast('Round opened successfully.', 'success')
        }

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
            '.round.opened': e => updateRoundState(e.round, 'reset'),
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
    
    const inputBuffer = ref("")
    const manualInput = ref("")
    const canClaim = ref(false)
    const currentTicket = ref<any>(null)
    const isVerifying = ref(false)
    const isSubmitting = ref(false)
    const scannedTicket = ref(null as string | null)
    const scannedStatus = ref(null as string | null)
    const ticketComp = ref<InstanceType<typeof TicketReceipt> | null>(null)

    function confirmBet() {

        if (isSubmitting.value) return

        if (betAmount.value <= 0) {
            addToast('Enter a valid bet amount.', 'error')
            return
        }

        isSubmitting.value = true
        tellerWallet.value.current = Number(tellerWallet.value.current) + betAmount.value

        router.post(
            route('teller.bet.index', currentRound.value?.event_id), 
            {
                amount: betAmount.value,
                round_id: currentRound.value?.id,
                side: side.value
            },
            {
                onSuccess: () => {
                    const newTicket = (usePage().props.flash as Record<string, any>).newTicket;
                    if (newTicket) {
                        tickets.value.unshift(newTicket);
                        currentTicket.value = newTicket
                        nextTick(() => {
                            ticketComp.value?.printReceipt()
                        })
                        addToast('Bet confirmed and printing...', 'success')
                    } else {
                        addToast('Bet placed but failed to retrieve ticket for printing.', 'error')
                    }

                    betAmount.value = 0
                    showConfirm.value = false
                    isSubmitting.value = false
                },
                onError: () => {
                    addToast('Failed to place bet. Please try again.', 'error')
                    tellerWallet.value.current = Number(tellerWallet.value.current) - betAmount.value
                    isSubmitting.value = false
                },
                onFinish: () => {
                    isSubmitting.value = false
                }
            }
        )
    }

    function isSideClosed(side: Side): boolean {
        return currentRound.value?.[`${side}_closed`] ?? false
    }

    const verifyTicket = async (code: string) => {
        if (!code || isVerifying.value) return;

        scannedTicket.value = null;
        canClaim.value = false;

        isVerifying.value = true;
        try {
            const response = await axios.get(`/teller/bet/${code}/verify`);
            
            // Populate the modal data
            scannedTicket.value = response.data.ticket;
            scannedStatus.value = response.data.status;
            canClaim.value = response.data.can_payout;
        } catch (error: any) {
            const serverMessage = error.response?.data?.message;
            if (serverMessage) {
                addToast(serverMessage, 'error');
            } else if (error.response?.status === 404) {
                addToast('Ticket not found', 'error');
            }
        } finally {
            isVerifying.value = false;
        }
    };

    const supervisorModal = ref<InstanceType<typeof SupervisorTransaction> | null>(null);

    const processInput = (value: string) => {
        const cleanValue = value.trim();
        if (!cleanValue) return;

        if (cleanValue.startsWith('supervisor_')) {
            supervisorModal.value?.open(cleanValue);
        } else if (cleanValue.length > 5) {
            verifyTicket(cleanValue);
        }
    };

    const handleScannerInput = (e: KeyboardEvent) => {
        // Keep your guard: Ignore if user is manually typing in any input box
        if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) {
            return;
        }

        if (e.key === 'Enter') {
            processInput(inputBuffer.value);
            inputBuffer.value = ""; 
        } else {
            // Prevent control keys (Shift, Alt, etc.) from being added to the buffer
            if (e.key.length === 1) {
                inputBuffer.value += e.key;
            }
        }
    };

    const handleManualSubmit = () => {
        processInput(manualInput.value);
        manualInput.value = ""; // Clear the box after submission
    };

    const isProcessingRefund = ref(false)
    const isProcessingPayout = ref(false)
    const payoutTicketRef = ref<InstanceType<typeof PayoutTicket> | null>(null)
    const currentPaidTicket = ref<any>(null)
    const currentRefundTicket = ref<any>(null)

    const handlePayoutProcess = async (ticketNumber: string) => {
        if (isProcessingPayout.value) return;
            
        isProcessingPayout.value = true;
        try {
            const response = await axios.post(`/teller/bet/${ticketNumber}/claim`);
            
            // 1. Assign the data to the hidden print component
            currentPaidTicket.value = response.data.ticket;

            // 2. Wait for Vue to render the component with data, then print
            nextTick(() => {
                payoutTicketRef.value?.printReceipt();
                addToast('Payout Successful!', 'success');
                
                // 3. Reset state
                scannedTicket.value = null;
            });

        } catch (error: any) {
            addToast(error.response?.data?.message || 'Error processing payout', 'error');
        } finally {
            isProcessingPayout.value = false;
        }
    };

    const handleRefundProcess = async(ticketNumber: string) => {
        if (isProcessingRefund.value) return;

        isProcessingRefund.value = true;
        try {
            const response = await axios.post(`/teller/bet/${ticketNumber}/refund`);

            currentRefundTicket.value = response.data.ticket;

            tellerWallet.value.current = Number(tellerWallet.value.current) - Number(currentRefundTicket.value.amount)
    
            addToast('Refund successful!', 'success');
            scannedTicket.value = null;

        } catch (error: any) {
            addToast(error.response?.data?.message || 'Error processing refund', 'error');
        } finally {
            isProcessingRefund.value = false;
        }
    };

    // 4. Lifecycle Hooks
    onMounted(() => {
        initializeEcho()
        window.addEventListener('keypress', handleScannerInput)
    })

    onUnmounted(() => {
        echo?.leave('rounds')
        window.removeEventListener('keypress', handleScannerInput)
    })

    const onBalanceUpdated = (newBalance: number) => {
        tellerWallet.value.current = newBalance;
    };

    const isFetching = ref(false);

    const handleReprintAction = async (ticketSummary: any) => {
        if (isFetching.value) return;

        try {
            isFetching.value = true;

            // 1. Fetch the full details from the server
            const response = await axios.get(`/teller/bet/tickets/${ticketSummary.id}`);
            
            // 2. Assign the full data to the reactive variable
            currentTicket.value = response.data;

            // 3. Wait for DOM update and trigger print
            await nextTick();
            
            if (ticketComp.value) {
                ticketComp.value.printReceipt();
            }
        } catch (error) {
            console.error("Failed to fetch ticket details:", error);
            alert("Could not load ticket data for reprinting.");
        } finally {
            isFetching.value = false;
        }
    };
</script>

<template>
    <SupervisorTransaction 
        ref="supervisorModal"
        :current-balance="tellerWallet.current"
        :event-id="currentRound?.event_id"
        :teller-id="tellerWallet.id"
        @updated="onBalanceUpdated"
    />
     
    <div style="display: none;">
        <TicketReceipt 
            v-if="currentTicket" 
            :key="currentTicket.id"
            ref="ticketComp" 
            :ticket="currentTicket" 
        />

        <PayoutTicket 
            v-if="currentPaidTicket" 
            ref="payoutTicketRef" 
            :ticket="currentPaidTicket" 
        />
    </div>
    <Toast />
    <TicketModal 
        v-if="scannedTicket" 
        :ticket="scannedTicket" 
        :status="scannedStatus ?? ''" 
        :can-payout="canClaim"
        @close="scannedTicket = null"
        @refund="handleRefundProcess"
        @confirm="handlePayoutProcess"
    />
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
                    (!walaClosed && roundStatus === 'open')
                    ? 'bg-blue-600 hover:bg-blue-700 active:scale-95 cursor-pointer' 
                    : 'bg-blue-400 cursor-not-allowed opacity-50'
                ]">
                <span>WALA</span>
                <span v-if="roundStatus !== 'open'" class="block text-xs mt-1">
                    (BETTING CLOSED)
                </span>
                <span v-else-if="walaClosed" class="block text-xs mt-1">
                    (SIDE CLOSED)
                </span>
            </button>

            <button
                @click="placeBet('draw')"
                :disabled="drawClosed"
                :class="[
                    'text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg transition transform duration-150 w-full',
                    (!drawClosed && roundStatus === 'open')
                    ? 'bg-yellow-600 hover:bg-yellow-700 active:scale-95 cursor-pointer' 
                    : 'bg-yellow-400 cursor-not-allowed opacity-50'
                ]"
                >
                <span>DRAW</span>
                <span v-if="roundStatus !== 'open'" class="block text-xs mt-1">
                    (BETTING CLOSED)
                </span>
                <span v-else-if="drawClosed" class="block text-xs mt-1">
                    (SIDE CLOSED)
                </span>
            </button>

            <button
                @click="placeBet('meron')"
                :disabled="meronClosed"
                :class="[
                    'text-white py-4 md:py-6 text-xl font-extrabold rounded-2xl shadow-lg transition transform duration-150 w-full',
                    (!meronClosed && roundStatus === 'open')
                    ? 'bg-red-600 hover:bg-red-700 active:scale-95 cursor-pointer' 
                    : 'bg-red-400 cursor-not-allowed opacity-50'
                ]"
                >
                <span>MERON</span>
                <span v-if="roundStatus !== 'open'" class="block text-xs mt-1">
                    (BETTING CLOSED)
                </span>
                <span v-else-if="meronClosed" class="block text-xs mt-1">
                    (SIDE CLOSED)
                </span>
            </button>

            <BetConfirm
                :visible="showConfirm"
                :loading="isSubmitting"
                :amount="betAmount"
                :side="side"
                :remaining-balance="tellerWallet.current"
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
            <p class="font-bold text-lg"><CircleUserRound class="display-inline-block"/>  {{ teller.name }}</p>
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

        <div class="mt-4 space-y-2 text-sm">
            <div class="w-full">
                <div class="flex flex-col space-y-1 w-full">
                    <label for="ticket-input" class="font-semibold text-gray-700">
                        Manual Ticket Entry:
                    </label>
                    
                    <div class="flex w-full gap-2">
                        <input 
                            id="ticket-input"
                            v-model="manualInput" 
                            type="text" 
                            placeholder="Type Ticket # here..."
                            @keydown.enter.prevent="handleManualSubmit"
                            class="flex-1 min-w-0 rounded border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"
                        />
                        
                        <button 
                            @click="handleManualSubmit" 
                            class="shrink-0 rounded bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-700 active:bg-blue-800 transition-colors"
                        >
                            Verify
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Capital Info -->
        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
            <span>Wallet</span>
            <span class="font-bold">
                ₱ {{ 
                    Number(tellerWallet.initial).toLocaleString('en-US', { 
                        minimumFractionDigits: 2, 
                        maximumFractionDigits: 2 
                    }) 
                }}
            </span>
            </div>
            <div class="flex justify-between">
            <span>Remaining</span>
            <span class="font-bold text-green-600">
                ₱ {{ 
                    Number(tellerWallet.current).toLocaleString('en-US', { 
                        minimumFractionDigits: 2, 
                        maximumFractionDigits: 2 
                    }) 
                }}
            </span>
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
                <span class="text-red-600 font-medium">MERON ({{ sumStats.meron_payout }})</span>
                <span class="font-bold">₱{{ formatNumber(sumStats.meron_total, 0) }}</span>
                <span v-if="meronClosed || roundStatus !== 'open'" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
            </div>

            <div class="flex justify-between">
                <span class="text-yellow-600 font-medium">DRAW (1.7)</span>
                <span class="font-bold">₱{{ formatNumber(sumStats.draw_total, 0) }}</span>
                <span v-if="drawClosed || roundStatus !== 'open'" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
            </div>

            <div class="flex justify-between">
                <span class="text-green-600 font-medium">WALA ({{ sumStats.wala_payout }})</span>
                <span class="font-bold">₱{{ formatNumber(sumStats.wala_total, 0) }}</span>
                <span v-if="walaClosed || roundStatus !== 'open'" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full">CLOSED</span>
                <span v-else class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full font-semibold">OPEN</span>
            </div>

            <div class="flex justify-between border-t pt-2 font-semibold">
                <span>Total</span>
                <span>₱ {{ formatNumber(sumStats.meron_total + sumStats.wala_total + sumStats.draw_total) }}</span>
            </div>
        </div>

        <!-- Recent Bets -->
        <div class="mt-6 border-t pt-4"><p class="font-semibold mb-3">Recent Bets <span class="float-right">Winner</span></p></div>
        <div class="flex-1 overflow-y-auto max-h-96">
            <ul class="space-y-2 text-sm">
                <li v-for="ticket in tickets" :key="ticket.id" class="flex justify-between bg-gray-50 p-2 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="font-mono font-medium">{{ ticket.ticket_number }}</span>
                        <span :class="{
                            'text-green-600': ticket.status === 'won',
                            'text-red-500': ticket.status === 'lost',
                            'text-gray-500': ticket.status === 'pending'
                        }" class="font-bold ms-2">
                            {{ ticket.status.toUpperCase() }}
                        </span>
                    </div>
                    <div class="flex gap-2 mt-2 border-t pt-2" v-if="ticket.status === 'pending'">
                        <button 
                            @click="handleReprintAction(ticket)"
                            class="flex-1 bg-blue-100 text-blue-700 p-2 cursor-pointer rounded text-xs font-bold hover:bg-blue-200 transition-colors"
                        >
                            REPRINT
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Logs -->
        <div class="mt-6 border-t pt-4"><p class="font-semibold mb-3">Recent Rounds <span class="float-right">Winner</span></p></div>
        <div class="flex-1 overflow-y-auto max-h-96">
            <ul class="space-y-2 text-sm">
                <li v-for="item in rounds" :key="item.id" class="flex justify-between bg-gray-50 p-2 rounded-lg">
                    <span>#{{ item.round_number }}</span>
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