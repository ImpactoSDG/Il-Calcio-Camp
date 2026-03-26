<template>
    <div class="row justify-content-center mt-5 pt-4">
        <div class="col-md-6 col-lg-5">
            <div class="modern-card animate-fade-in">
                <div class="modern-card-header">
                    <h2>CREAR CUENTA</h2>
                    <p class="text-muted small">Registra un nuevo usuario en el sistema</p>
                </div>

                <div class="modern-card-body">
                    <form @submit.prevent="handleRegister">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reg-nombre" class="form-label small fw-bold text-secondary">NOMBRE</label>
                                <input type="text" id="reg-nombre" v-model="nombre" class="form-control" required placeholder="Nombre completo">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reg-email" class="form-label small fw-bold text-secondary">EMAIL</label>
                                <input type="email" id="reg-email" v-model="email" class="form-control" required placeholder="correo@ejemplo.com">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reg-contrasena" class="form-label small fw-bold text-secondary">CONTRASEÑA</label>
                                <input type="password" id="reg-contrasena" v-model="contrasena" class="form-control" required placeholder="******">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="reg-confirm-contrasena" class="form-label small fw-bold text-secondary">REPETIR CONTRASEÑA</label>
                                <input type="password" id="reg-confirm-contrasena" v-model="confirmContrasena" class="form-control" required placeholder="******">
                            </div>
                        </div>
                        <div class="mb-4">
                    <label for="id_rol" class="form-label small fw-bold text-secondary">Tipo de Usuario (Rol):</label>
                    <select id="id_rol" v-model="id_rol" class="form-select shadow-sm" required>
                        <option :value="null" disabled>Seleccione un rol...</option>
                        <option v-for="rol in filteredRoles" :key="rol.id" :value="rol.id">
                            {{ rol.nombre }}
                        </option>
                    </select>
                </div>
                
                <button type="submit" :disabled="isLoading" class="btn btn-dark w-100 py-2 shadow-sm">
                    <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ isLoading ? 'REGISTRANDO...' : 'REGISTRAR USUARIO' }}
                </button>
                </form>
                </div>
                
                <div class="card-footer text-center bg-transparent border-0 pb-4">
                    <router-link to="/login" class="text-decoration-none small fw-bold text-primary">
                        ¿Ya tienes cuenta? Inicia sesión
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router'; 
import { useToastStore } from '@/stores/toastStore';
import api from '@/services/api';

const router = useRouter();
const nombre = ref('');
const email = ref('');
const contrasena = ref('');
const confirmContrasena = ref('');
const id_rol = ref(null);
const roles = ref([]);
const filteredRoles = computed(() => {
    return roles.value.filter(role => role.nombre.toLowerCase() !== 'admin');
});
const isLoading = ref(false);
const toast = useToastStore();

onMounted(async () => {
    try {
        const response = await api.get('/roles');
        roles.value = response.data;
    } catch (err) {
        console.error('Error al cargar roles:', err);
        toast.showToast({
            message: 'No se pudieron cargar los roles.',
            type: 'danger'
        });
    }
});

const handleRegister = async () => {
    if (contrasena.value !== confirmContrasena.value) {
        toast.showToast({
            message: 'Las contraseñas no coinciden.',
            type: 'danger'
        });
        return;
    }
    if (contrasena.value.length < 6) {
        toast.showToast({
            message: 'La contraseña debe tener al menos 6 caracteres.',
            type: 'danger'
        });
        return;
    }
    isLoading.value = true;
    try {
        const response = await api.post('/register', {
            nombre: nombre.value,
            email: email.value,
            contrasena: contrasena.value,
            id_rol: id_rol.value,
        });

        toast.showToast({
            message: response.data.message + '. Redirigiendo...',
            type: 'success'
        });

        setTimeout(() => {
            router.push('/login');
        }, 1500);

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