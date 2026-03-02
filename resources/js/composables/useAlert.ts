import { ref } from 'vue'

export const alertState = ref({
    show: false,
    type: 'error',
    message: '',
})

export function useAlert() {
    const showAlert = (message: string, type: 'error' | 'success' | 'warning' = 'error') => {
        alertState.value = {
            show: true,
            type,
            message
        }
    }

    const closeAlert = () => {
        alertState.value.show = false
    }

    return { showAlert, closeAlert }
}