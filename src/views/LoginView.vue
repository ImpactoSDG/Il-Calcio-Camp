<template>
  <div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-4">
      <div class="card shadow-lg border-0">
        <div class="card-header text-center bg-dark text-white">
          <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i> Iniciar Sesión</h4>
        </div>
        <div class="card-body p-4">
          <form @submit.prevent="handleLogin">
            
            <div class="mb-3">
              <label for="email" class="form-label">Email:</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input 
                  type="email" 
                  id="email" 
                  v-model="email" 
                  class="form-control" 
                  required 
                  placeholder="tu.correo@ejemplo.com"
                >
              </div>
            </div>

            <div class="mb-4">
              <label for="contrasena" class="form-label">Contraseña:</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input 
                  type="password" 
                  id="contrasena" 
                  v-model="contrasena" 
                  class="form-control" 
                  required 
                  placeholder="Ingresa tu contraseña"
                >
              </div>
            </div>
            
            <button type="submit" :disabled="isLoading" class="btn btn-dark w-100 py-2">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
              {{ isLoading ? 'Verificando...' : 'Entrar' }}
            </button>
            
          </form>
        </div>
        <!-- <div class="card-footer text-center">
          <router-link to="/register" class="link-custom small">¿No tienes cuenta? Regístrate aquí</router-link>
        </div> -->
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
    userStore.login(userData);
    
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