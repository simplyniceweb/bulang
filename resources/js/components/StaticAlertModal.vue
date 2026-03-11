<script setup lang="ts">
import { alertState, useAlert } from '@/composables/useAlert'

const { closeAlert } = useAlert()

const colors = {
  error: 'bg-red-600',
  success: 'bg-green-600',
  warning: 'bg-yellow-500'
}

const style = colors[alertState.value.type ?? 'error']
</script>

<template>
  <div v-if="alertState.show" class="fixed inset-0 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-xl shadow-lg w-96 p-6 text-center">
      <h2 class="text-lg font-bold mb-4">Notice</h2>

      <p class="text-gray-700 mb-6">
        {{ alertState.message }}
      </p>

      <button
        @click="closeAlert"
        :class="['px-4 py-2 text-white rounded-lg', style]"
      >
        OK
      </button>
    </div>
  </div>
</template>