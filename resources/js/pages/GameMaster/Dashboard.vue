<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CircleUserRound } from 'lucide-vue-next';
import { ref } from 'vue';
import AlertModal from '@/components/AlertModal.vue'
import ConfirmModal from '@/components/ConfirmModal.vue'
import StaticAlertModal from '@/components/StaticAlertModal.vue'
import Toast from '@/components/Toast.vue'
import { useAlert } from '@/composables/useAlert'
import { useFlashAlert } from '@/composables/useFlashAlert'
import { formatNumber } from '@/helpers/format';
import { addToast } from '@/helpers/toast';

const confirmModal = ref(false)
const confirmMessage = ref('')
const confirmAction = ref<() => void>(() => {})
const confirmType = ref('default')

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
    round_id: number | null,
    round_number: number | null,
    round_status: string | null,
}>()

/* --- Actions --- */
function startRound() {
    confirmMessage.value = 'Open a new round?'

    confirmAction.value = () => {
        router.post(
            route('game_master.round.open'),
            { noModal: true },
            {
                onSuccess: (page) => {
                    console.log(page.props.flash?.success);
                    addToast(page.props.flash?.success as string, 'success');
                }
            }
        )
    }

    confirmModal.value = true
    confirmType.value = 'success'
}

function cancelRound() {
    if (!props.round_id) {
        showAlert('No active round to cancel.')
        return
    }

    confirmMessage.value = 'Cancel current round? This will invalidate all bets placed for this round.'

    confirmAction.value = () => {
        router.post(
            route('game_master.round.cancel', props.round_id),
            { noModal: true },
            {
                onSuccess: (page) => {
                    console.log(page.props.flash?.success);
                    addToast(page.props.flash?.success as string, 'success');
                }
            }
        )
    }

    confirmModal.value = true
}

function closeSide(side: 'wala' | 'meron' | 'draw', isClosed: boolean | undefined) {
    if (!props.round_id) {
        showAlert('No active round to close betting for.')
        return
    }

    confirmModal.value = true
    confirmType.value = side

    if (isClosed) {
        confirmMessage.value = `${side.toUpperCase()} betting is already closed. Do you want to reopen it?`
    } else {
        confirmMessage.value = `Are you sure you want to close ${side.toUpperCase()} betting?`
    }

    confirmAction.value = () => {
        router.post(
            route('game_master.round.closeSide', props.round_id),
            { side, reopen: isClosed, noModal: true },
            {
                onSuccess: (page) => {
                    console.log(page.props.flash?.success);
                    addToast(page.props.flash?.success as string, isClosed ? 'success' : 'error');
                }
            }
        )
    }
}

function closeGlobalBetting() {
  if (!props.round_id) {
    showAlert('No active round to close global betting for.')
    return
  }
    confirmMessage.value = 'Are you sure you want to close all betting? This will prevent any further bets from being placed.'

    confirmAction.value = () => {
        router.post(
            route('game_master.round.closeGlobalBetting', props.round_id),
            { noModal: true },
            {
                onSuccess: (page) => {
                    console.log(page.props.flash?.success);
                    addToast(page.props.flash?.success as string, 'success');
                }
            })
    }

    confirmModal.value = true
    confirmType.value = 'danger'
}

function confirmWinner(side: 'wala' | 'meron' | 'draw') {
    if (!props.round_id) {
        showAlert('No active round to declare winner.')
        return
    }

    confirmMessage.value = `Declare winner: ${side.toUpperCase()}?`

    confirmAction.value = () => {
        router.post(
            route('game_master.round.declare', props.round_id),
            { winner: side, noModal: true },
            {
                onSuccess: (page) => {
                    console.log(page.props.flash?.success);
                    addToast(page.props.flash?.success as string, 'success');
                }
            }
        )
    }

    confirmModal.value = true
    confirmType.value = side

}

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
                <h2 class="text-xl font-bold text-gray-900">Current Round: #{{ props.round_number ?? 0 }}</h2>
                <p class="font-semibold">Status: 
                    <span :class="props.round_status==='open'?'text-green-600':'text-red-600'">{{ props.round_status?.toUpperCase() }}</span>
                </p>
                </div>
                <button
                @click="startRound"
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
                    @click="confirmWinner('wala')"
                    class="w-full py-3 rounded-lg text-white font-semibold transition bg-indigo-600 hover:bg-indigo-700"
                    >
                    WINNER WALA
                    </button>

                    <button
                    @click="confirmWinner('meron')"
                    class="w-full py-3 rounded-lg text-white font-semibold transition bg-red-600 hover:bg-red-700"
                    >
                    WINNER MERON
                    </button>

                    <button
                    @click="confirmWinner('draw')"
                    class="w-full py-3 rounded-lg text-white font-semibold transition bg-yellow-600 hover:bg-yellow-700"
                    >
                    WINNER DRAW
                    </button>
                </div>

                <div class="mt-4 bg-gray-50 p-4 rounded-xl grid grid-cols-3 text-center font-bold">
                    <div class="text-indigo-600 text-sm">WALA<br><span class="text-lg">{{ formatNumber(124512) }}</span></div>
                    <div class="text-yellow-600 text-sm">DRAW<br><span class="text-lg">{{ formatNumber(8612) }}</span></div>
                    <div class="text-red-600 text-sm">MERON<br><span class="text-lg">{{ formatNumber(23512) }}</span></div>
                </div>
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
                <span class="font-bold">Round #{{ item.id }}</span>
                <span :class="{'text-indigo-600': item.winner==='wala','text-red-600': item.winner==='meron','text-yellow-500': item.winner==='draw','text-gray-500': !item.winner}" class="font-bold">
                    {{ item.winner ? item.winner?.toUpperCase() : 'N/A' }}
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
