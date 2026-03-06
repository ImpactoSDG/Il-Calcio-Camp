<template>
  <transition name="toast-fade">
    <div 
      v-if="toast.visible"
      :class="['alert', alertClass, 'toast-notification']"
      role="alert"
    >
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <i :class="['bi', iconClass, 'me-2 fs-5']"></i>
          <p class="mb-0">{{ toast.message }}</p>
        </div>
        <button 
          type="button" 
          class="btn-close ms-2" 
          aria-label="Close" 
          @click="toast.hideToast()"
        ></button>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const typeMap = {
  success: { class: 'alert-success', icon: 'bi-check-circle-fill' },
  danger: { class: 'alert-danger', icon: 'bi-x-octagon-fill' },
  warning: { class: 'alert-warning', icon: 'bi-exclamation-triangle-fill' },
  info: { class: 'alert-info', icon: 'bi-info-circle-fill' },
};

const alertClass = computed(() => typeMap[toast.type]?.class || 'alert-info');
const iconClass = computed(() => typeMap[toast.type]?.icon || 'bi-info-circle-fill');
</script>

<style scoped>
.toast-notification {
  position: fixed;
  top: 10px;
  right: 10px;
  z-index: 999999;
  width: 350px;
  max-width: 90%;
  transition: all 0.3s ease-in-out;
  pointer-events: auto;
}

.toast-fade-enter-active,
.toast-fade-leave-active {
  transition: all 0.3s ease;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  transform: translateX(100%);
  opacity: 0;
}
.toast-fade-enter-to,
.toast-fade-leave-from {
  transform: translateX(0);
  opacity: 1;
}
</style>
