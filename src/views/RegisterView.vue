<template>
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-header text-center bg-dark text-white">
                    <h4 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Crear Usuario</h4>
                </div>

                <div class="card-body p-4">
                    <form @submit.prevent="handleRegister">
                        <div class="mb-3">
                            <label for="reg-nombre" class="form-label">Nombre de Usuario:</label>
                            <input type="text" id="reg-nombre" v-model="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="reg-email" class="form-label">Email:</label>
                            <input type="email" id="reg-email" v-model="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="reg-contrasena" class="form-label">Contraseña:</label>
                            <input type="password" id="reg-contrasena" v-model="contrasena" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label for="id_rol" class="form-label">ID de Rol (Temporal):</label>
                            <input type="number" id="id_rol" v-model.number="id_rol" class="form-control" required min="1">
                        </div>
                        <button type="submit" :disabled="isLoading" class="btn btn-dark w-100 py-2">
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ isLoading ? 'Registrando...' : 'Registrar' }}
                        </button>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <router-link to="/login" class=" small">¿Ya tienes cuenta? Inicia sesión</router-link>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useToastStore } from '@/stores/toastStore';
import api from '@/services/api';

const nombre = ref('');
const email = ref('');
const contrasena = ref('');
const id_rol = ref(null);
const isLoading = ref(false);
const toast = useToastStore();

const handleRegister = async () => {
    isLoading.value = true;
    try {
        const response = await api.post('/register', {
            nombre: nombre.value,
            email: email.value,
            contrasena: contrasena.value,
            id_rol: id_rol.value,
        });
        toast.showToast({
            message: response.data.message + '. Puedes iniciar sesión.',
            type: 'success'
        });
        nombre.value = '';
        email.value = '';
        contrasena.value = '';
        id_rol.value = null;
    } catch (err) {
        const errorMessage = err.response?.data?.message || 'Error de conexión o del servidor.';
        toast.showToast({
            message: errorMessage,
            type: 'danger',
            duration: 5000
        });
        console.error('Error de registro:', err);
    } finally {
        isLoading.value = false;
    }
}
</script>

<style scoped>

</style>