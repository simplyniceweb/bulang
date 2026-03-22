<script setup lang="ts">
import { computed } from 'vue';
import { formatNumber } from '@/helpers/format';

const props = defineProps<{
    ticket: any; // The ticket object contains the 'round' object
    status: string;
    canPayout: boolean;
}>();

const emit = defineEmits(['close', 'refund', 'confirm']);

const statusMap: Record<string, { label: string; class: string; icon: string }> = {
    winner: { label: 'WINNING TICKET', class: 'bg-green-600', icon: '🏆' },
    loser: { label: 'LOSING TICKET', class: 'bg-red-600', icon: '❌' },
    cancelled: { label: 'ROUND CANCELLED', class: 'bg-gray-700', icon: '⚠️' },
    already_paid: { label: 'ALREADY CLAIMED', class: 'bg-orange-500', icon: '💰' },
    refunded: { label: 'ALREADY REFUNDED', class: 'bg-orange-500', icon: '💰' },
    waiting_result: { label: 'RESULT PENDING', class: 'bg-yellow-500', icon: '⏳' },
    betting_open: { label: 'BETTING ON GOING', class: 'bg-blue-500', icon: '⚔️' },
};

const currentStatus = computed(() => statusMap[props.status] || { label: 'UNKNOWN', class: 'bg-gray-500', icon: '?' });

// Helper to format the winner name
const roundWinner = computed(() => {
    const winner = props.ticket.round?.winner;
    if (!winner) return 'NONE';
    return winner.toUpperCase();
});
</script>

<template>
    <div v-if="ticket" class="fixed inset-0 bg-black/80 flex items-center justify-center z-999 p-4 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-2xl overflow-hidden shadow-2xl border-2 border-gray-100">
            
            <div :class="currentStatus.class" class="p-6 text-center text-white shadow-inner">
                <div class="text-4xl mb-2">{{ currentStatus.icon }}</div>
                <h2 class="text-3xl font-black uppercase tracking-tighter">{{ currentStatus.label }}</h2>
            </div>

            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between p-3 bg-gray-900 rounded-lg text-white">
                    <span class="text-lg font-bold text-gray-400">ROUND #{{ ticket.round_id }}</span>
                    <span :class="ticket.round?.winner === 'meron' ? 'text-red-500' : 'text-blue-400'" class="font-black text-xl">
                        RESULT : {{ roundWinner }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="border p-3 rounded-xl bg-gray-50">
                        <p class="text-gray-500 text-sm uppercase font-bold">Ticket Number</p>
                        <p class="font-mono font-bold text-lg">{{ ticket.ticket_number }}</p>
                    </div>
                    <div class="border p-3 rounded-xl bg-gray-50 text-right">
                        <p class="text-gray-500 text-sm uppercase font-bold">Your Bet</p>
                        <p class="font-black text-lg uppercase" :class="ticket.side === 'meron' ? 'text-red-600' : 'text-blue-600'">
                            {{ ticket.side }}
                        </p>
                    </div>
                </div>

                <div class="p-4 bg-green-50 border-2 border-green-100 rounded-2xl text-center">
                    <p class="text-xs text-green-700 font-bold uppercase tracking-widest">
                        {{ status === 'cancelled' ? 'Refund Amount' : 'Potential Payout' }}
                    </p>
                    <p class="text-5xl font-black text-green-900 mt-1">
                        ₱{{ status === 'cancelled' ? formatNumber(ticket.amount) : formatNumber(ticket.potential_payout) }}
                    </p>
                    <p class="text-sm text-green-600 mt-2 font-medium">ODDS: {{ ticket.odds }} <br> BET AMOUNT: ₱{{ formatNumber(ticket.amount) }}</p>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="emit('close')" class="flex-1 px-4 py-4 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition uppercase text-sm">
                        Close
                    </button>
                    <button 
                        v-if="status === 'betting_open'" 
                        @click="emit('refund', ticket.ticket_number)"
                        class="flex-2 px-4 py-4 bg-black text-white font-black rounded-xl hover:bg-green-600 transition uppercase shadow-lg shadow-black/20 text-sm tracking-wide"
                    >
                        Refund Ticket
                    </button>
                    <button 
                        v-if="canPayout" 
                        @click="emit('confirm', ticket.ticket_number)"
                        class="flex-2 px-4 py-4 bg-black text-white font-black rounded-xl hover:bg-green-600 transition uppercase shadow-lg shadow-black/20 text-sm tracking-wide"
                    >
                        Confirm Payout
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>