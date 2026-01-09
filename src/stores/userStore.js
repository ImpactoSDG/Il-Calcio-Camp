import { defineStore } from 'pinia'
import router from '@/router'
import usuariosService from '@/services/usuariosService'

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
    logout() {
      this.$reset()
      router.push('/')
    },
    async refreshModulos() {
      if (!this.user?.id) return;
      try {
        const data = await usuariosService.refreshModulos(this.user.id);
        if (data.modulos) {
          this.user.modulos = data.modulos;
        }
      } catch (e) {
        console.error("Error refreshing modules", e);
      }
    }
  },

  persist: {
    storage: sessionStorage,
  },
})
