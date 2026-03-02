<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  show: boolean
  message: string
  type?: 'error' | 'success'
}>()

defineEmits(['close'])

const colors = {
  error: {
    title: 'text-red-600',
    button: 'bg-red-600 hover:bg-red-700'
  },
  success: {
    title: 'text-green-600',
    button: 'bg-green-600 hover:bg-green-700'
  }
}

const style = computed(() => colors[props.type ?? 'error'])
</script>

<template>
<div
  v-if="show"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
>
  <div class="bg-white rounded-xl shadow-lg p-6 w-90 text-center">

    <h2 class="text-lg font-bold mb-3" :class="style.title">
    {{ props.type === 'success' ? 'Success' : 'System Error' }}
    </h2>

    <p class="text-gray-700 mb-5">
      {{ message }}
    </p>

    <button
      @click="$emit('close')"
      class="text-white px-4 py-2 rounded-lg"
      :class="style.button"
    >
      OK
    </button>

  </div>
</div>
</template>