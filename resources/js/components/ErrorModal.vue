<script setup lang="ts">
const props = defineProps<{
  show: boolean
  message: string
}>()

const emit = defineEmits(['close'])
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
  >
    <div class="bg-white rounded-xl shadow-lg p-6 w-[360px] text-center">

      <h2 class="text-lg font-bold text-red-600 mb-3">
        System Error
      </h2>

      <p class="text-gray-700 mb-5">
        {{ message }}
      </p>

      <button
        @click="$emit('close')"
        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700"
      >
        OK
      </button>

    </div>
  </div>
</template>