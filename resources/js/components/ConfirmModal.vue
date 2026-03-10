<script setup lang="ts">
import { computed } from 'vue';

// 1. Define the props. Note: colorType is now optional
const props = defineProps<{
  show: boolean
  title?: string
  message: string
  confirmText?: string
  cancelText?: string
  colorType?: string // e.g., 'meron', 'wala', 'draw'
}>()

// 2. Define the color map as a constant
const danger = 'bg-red-600 hover:bg-red-700'
const info = 'bg-blue-600 hover:bg-blue-700'
const warning = 'bg-yellow-500 hover:bg-yellow-600'
const success = 'bg-green-600 hover:bg-green-700'

const COLORS = {
  meron: danger,
  danger: danger,
  wala: info,
  info: info,
  draw: warning,
  warning: warning,
  default: success,
  success: success,
} as const;

// 3. Create a Type for safety
type ColorKey = keyof typeof COLORS;

// 4. Use a computed property to determine the class.
// This resolves the collision by using a unique name 'currentClass'
const currentClass = computed(() => {
  const key = props.colorType as ColorKey;
  return COLORS[key] ?? COLORS.default;
});

defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-[999]"
    >
      <div class="bg-white rounded-xl shadow-lg p-6 w-96 text-center">
        <h2 class="text-lg font-bold mb-4">
          {{ title ?? 'Confirm Action' }}
        </h2>

        <p class="mb-6 text-gray-600">
          {{ message }}
        </p>

        <div class="flex justify-center gap-4">
          <button
            @click="$emit('cancel')"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors"
          >
            {{ cancelText ?? 'Cancel' }}
          </button>

          <button
            @click="$emit('confirm')"
            :class="['px-4 py-2 text-white rounded-lg transition-colors shadow-sm', currentClass]"
          >
            {{ confirmText ?? 'Confirm' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>