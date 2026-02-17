<script setup lang="ts">
import { computed } from 'vue'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'

interface Props {
  visible: boolean
  amount: number
  side: 'meron' | 'wala' | 'draw'
  remainingBalance: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const sideClass = computed(() => {
  switch (props.side) {
    case 'meron': return 'bg-red-600'
    case 'wala': return 'bg-green-600'
    case 'draw': return 'bg-yellow-500'
  }
  return ''
})

const confirm = () => emit('confirm')
const cancel = () => emit('cancel')
</script>

<template>
  <Dialog :open="props.visible" @openChange="(val: any) => !val && cancel()">
    <DialogContent class="max-w-md p-6 rounded-2xl">
      <DialogHeader class="text-center">
        <DialogTitle class="text-xl font-bold text-gray-900">
          Confirm Your Bet
        </DialogTitle>
        <DialogDescription class="mt-2 text-sm text-gray-500">
          Review your bet before confirming
        </DialogDescription>
      </DialogHeader>

      <!-- Bet Details -->
      <div class="mt-4 flex flex-col gap-3">
        <div class="flex justify-between items-center border-b pb-2">
          <span class="font-semibold">Amount</span>
          <span class="text-indigo-600 font-bold text-2xl">₱{{ props.amount }}</span>
        </div>

        <div class="flex justify-between items-center border-b pb-2">
          <span class="font-semibold">Bet On</span>
          <span :class="['px-4 py-1 rounded-lg text-white font-bold', sideClass]">
            {{ props.side.toUpperCase() }}
          </span>
        </div>

        <div class="flex justify-between items-center">
          <span class="font-semibold">Remaining Balance</span>
          <span class="text-green-600 font-bold">₱{{ props.remainingBalance }}</span>
        </div>
      </div>

      <DialogFooter class="mt-6 flex gap-4">
        <button
          @click="cancel"
          class="flex-1 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition font-semibold"
        >
          Cancel
        </button>

        <button
          @click="confirm"
          class="flex-1 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition"
        >
          Confirm
        </button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
