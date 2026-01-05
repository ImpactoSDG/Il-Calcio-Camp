import { defineStore } from 'pinia'
import router from '@/router'
import api from '@/services/api' // Asegúrate de importar tu axios configurado

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
    userRole: (state) => state.user?.rol_nombre || null,
    userRoleId: (state) => Number(state.user?.id_rol) || null,
  },

  actions: {
    login(userData) {
      this.user = userData
    },
    // NUEVA ACCIÓN: Refresca solo los módulos del usuario logueado
    async refreshModulos() {
      if (!this.user?.id) return;
      try {
        // Asumiendo que tienes un endpoint que devuelva los módulos del usuario actual
        const response = await api.get(`/usuarios/${this.user.id}/modulos`);
        if (response.data) {
          this.user.modulos = response.data;
        }
      } catch (error) {
        console.error("Error al refrescar permisos en tiempo real", error);
      }
    },
    logout() {
      this.$reset()
      router.push('/')
    },
  },

  persist: {
    storage: sessionStorage,
  },
})