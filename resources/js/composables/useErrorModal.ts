import { usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'

export function useErrorModal() {

  const page = usePage()

  const errors = computed(() => page.props.errors)

  const showErrorModal = ref(false)
  const errorMessage = ref('')

  watch(errors, (newErrors: any) => {

    if (!newErrors) return

    const firstError = Object.values(newErrors)[0]

    if (firstError) {
      errorMessage.value = firstError as string
      showErrorModal.value = true
    }

  }, { immediate: true })

  const closeErrorModal = () => {
    showErrorModal.value = false
  }

  return {
    showErrorModal,
    errorMessage,
    closeErrorModal
  }
}