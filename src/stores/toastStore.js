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

  return {
    message,
    type,
    visible,
    showToast,
    hideToast,
  };
});