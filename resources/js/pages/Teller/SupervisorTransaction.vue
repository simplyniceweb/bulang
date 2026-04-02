<template>
  <div v-if="isVisible" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
      <div class="bg-indigo-600 p-4 text-white flex justify-between items-center">
        <h3 class="font-bold text-lg">Supervisor Authorization</h3>
        <button @click="close" class="text-white/80 hover:text-white">&times;</button>
      </div>

      <div class="p-6">
        <div class="flex justify-between items-end mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
          <div>
            <p class="text-xs text-gray-500 uppercase font-semibold">Current Teller Balance</p>
            <p class="text-2xl font-mono font-bold text-gray-800">₱ {{ currentBalance }}</p>
          </div>
          <div class="text-right">
            <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full">Active Event</span>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Transaction Type</label>
            <div class="grid grid-cols-2 gap-2">
              <button 
                @click="form.type = 'cash_in'"
                :class="form.type === 'cash_in' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600'"
                class="py-2 rounded-md font-medium transition-colors">
                Cash In (+)
              </button>
              <button 
                @click="form.type = 'cash_out'"
                :class="form.type === 'cash_out' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-600'"
                class="py-2 rounded-md font-medium transition-colors">
                Cash Out (-)
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Amount</label>
            <input 
              type="number" 
              v-model="form.amount" 
              class="w-full border-2 border-gray-200 rounded-lg p-3 text-lg focus:border-indigo-500 outline-none transition-all"
              placeholder="0.00"
              autofocus
            >
          </div>

          <button 
            @click="submit" 
            :disabled="loading || !form.amount"
            class="w-full bg-gray-800 hover:bg-black text-white py-4 rounded-lg font-bold text-lg shadow-lg disabled:opacity-50 transition-all">
            {{ loading ? 'Processing...' : 'Confirm Transaction' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps(['currentBalance', 'eventId', 'tellerId']);
const emit = defineEmits(['updated']);

const isVisible = ref(false);
const loading = ref(false);
const supervisorKey = ref('');

const form = reactive({
  amount: null,
  type: 'cash_in'
});

// This opens the modal when the parent detects the QR code
const open = (key) => {
  supervisorKey.value = key;
  isVisible.value = true;
};

const close = () => {
  isVisible.value = false;
  form.amount = null;
};

const submit = async () => {
  loading.value = true;
  try {
    const { data } = await axios.post('/teller/transactions/adjust-wallet', {
      supervisor_key: supervisorKey.value,
      teller_id: props.tellerId,
      event_id: props.eventId,
      amount: form.amount,
      type: form.type
    });
    
    emit('updated', data.new_balance);
    close();
  } catch (e) {
    alert(e.response?.data?.message || 'Transaction failed');
  } finally {
    loading.value = false;
  }
};

// Expose the "open" method to the parent
defineExpose({ open });
</script>