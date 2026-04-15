import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
  const message = ref('');
  const type = ref('success');
  const visible = ref(false);
  let timeoutId = null;

  function showToast({ message: msg, type: toastType = 'success', duration = 4000 }) {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }

    message.value = msg;
    type.value = toastType;
    visible.value = true;

    timeoutId = setTimeout(() => {
      hideToast();
    }, duration);
  }

  function hideToast() {
    visible.value = false;
    clearTimeout(timeoutId);
    timeoutId = null;
  }

  // Métodos helper para tipos específicos
  function success(msg, duration = 4000) {
    showToast({ message: msg, type: 'success', duration });
  }

  function error(msg, duration = 4000) {
    showToast({ message: msg, type: 'error', duration });
  }

  function warning(msg, duration = 4000) {
    showToast({ message: msg, type: 'warning', duration });
  }

  function info(msg, duration = 4000) {
    showToast({ message: msg, type: 'info', duration });
  }

  return {
    message,
    type,
    visible,
    showToast,
    hideToast,
    success,
    error,
    warning,
    info,
  };
});