import { defineStore } from 'pinia'
import router from '@/router'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
    userRole: (state) => state.user?.rol_nombre ? state.user.rol_nombre.toLowerCase() : null,
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
  },

  persist: {
    storage: sessionStorage,
  },
})
