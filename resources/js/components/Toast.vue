<script setup lang="ts">
import { toasts } from '@/helpers/toast'
</script>

<template>
  <div class="fixed bottom-5 right-5 flex flex-col gap-2 z-999" style="z-index: 9999;">
    <div
      v-for="toast in toasts"
      :key="toast.id"
      :class="[
        'px-4 py-2 rounded-lg shadow-md text-white font-semibold',
        toast.type === 'success' ? 'bg-green-600' :
        toast.type === 'error' ? 'bg-red-600' : 'bg-gray-600'
      ]"
    >
      {{ toast.message }}
    </div>
  </div>
</template>