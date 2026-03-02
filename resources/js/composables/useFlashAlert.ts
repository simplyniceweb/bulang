import { usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'

export function useFlashAlert() {

  const page = usePage()

  const flash = computed(() => page.props.flash)
  const errors = computed(() => page.props.errors)

  const show = ref(false)
  const message = ref('')
  const type = ref<'success' | 'error'>('success')

  /* SUCCESS FLASH */

  watch(flash, (f: any) => {

    if (!f) return

    if (f.success) {

      message.value = f.round_number
        ? `${f.success} (Round #${f.round_number})`
        : f.success

      type.value = 'success'
      show.value = true
    }

  }, { immediate: true })


  /* ERROR HANDLER */

  watch(errors, (e: any) => {

    if (!e) return

    const firstError = Object.values(e)[0]

    if (firstError) {
      message.value = firstError as string
      type.value = 'error'
      show.value = true
    }

  })


  const close = () => {
    show.value = false
  }

  return {
    show,
    message,
    type,
    close
  }

}