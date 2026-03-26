<template>
  <div class="row justify-content-center mt-5 pt-4">
    <div class="col-md-6 col-lg-4">
      <div class="modern-card animate-fade-in shadow-lg">
        <div class="modern-card-header text-center pt-4">
          <img src="/il-calcio-camp-logo.jpg" alt="Logo Il Calcio Camp" class="mb-3" style="max-height: 120px; border-radius: 50%;">
          <h2 class="fw-bold">IL CALCIO CAMP</h2>
          <p class="text-muted small">Inicia sesión para continuar</p>
        </div>
        <div class="modern-card-body">
          <form @submit.prevent="handleLogin">
            
            <div class="mb-3">
              <label for="email" class="form-label small fw-bold text-secondary">EMAIL</label>
              <div class="input-group">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-envelope text-secondary"></i></span>
                <input 
                  type="email" 
                  id="email" 
                  v-model="email" 
                  class="form-control bg-transparent border-0 shadow-none" 
                  required 
                  placeholder="tu.correo@ejemplo.com"
                >
              </div>
            </div>

            <div class="mb-4">
              <label for="contrasena" class="form-label small fw-bold text-secondary">CONTRASEÑA</label>
              <div class="input-group">
                <span class="input-group-text bg-transparent border-0"><i class="bi bi-key text-secondary"></i></span>
                <input 
                  type="password" 
                  id="contrasena" 
                  v-model="contrasena" 
                  class="form-control bg-transparent border-0 shadow-none" 
                  required 
                  placeholder="********"
                >
              </div>
            </div>
            
            <button type="submit" :disabled="isLoading" class="btn btn-primary-modern w-100 py-2 mt-2">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isLoading ? 'VERIFICANDO...' : 'ENTRAR' }}
            </button>
            
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { useToastStore } from '@/stores/toastStore';
import api from '@/services/api';

const router = useRouter();
const userStore = useUserStore();
const toast = useToastStore();
const email = ref('');
const contrasena = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
  isLoading.value = true;
  try {
    const response = await api.post('/login', {
      email: email.value,
      contrasena: contrasena.value,
    });
    
    const userData = response.data.usuario;
    const token = response.data.token;
    userStore.login(userData, token);
    
    toast.showToast({
      message: '¡Bienvenido! Has iniciado sesión correctamente.',
      type: 'success'
    });
    
    router.push('/menu');

  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Error de conexión o del servidor.';
    toast.showToast({
      message: errorMessage,
      type: 'danger',
      duration: 5000
    });
    console.error('Error de login:', err);
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>

</style>