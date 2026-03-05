import { defineStore } from 'pinia'
import router from '@/router'
import usuariosService from '@/services/usuariosService'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    token: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user && !!state.token,
    userRole: (state) => state.user?.rol_nombre ? state.user.rol_nombre.toLowerCase() : null,
    userRoleId: (state) => Number(state.user?.id_rol) || null,
  },

  actions: {
    login(userData, token) {
      this.user = userData
      this.token = token
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
