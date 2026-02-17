import { ref } from 'vue'

interface Toast {
  id: number
  message: string
  type: 'success' | 'error' | 'info'
}

export const toasts = ref<Toast[]>([])

let idCounter = 0

export function addToast(message: string, type: 'success' | 'error' | 'info' = 'info') {
  const id = ++idCounter
  toasts.value.push({ id, message, type })
  setTimeout(() => removeToast(id), 3000)
}

export function removeToast(id: number) {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index !== -1) toasts.value.splice(index, 1)
}