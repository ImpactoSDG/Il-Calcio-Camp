import { defineStore } from 'pinia'
import router from '@/router'
import usuariosService from '@/services/usuarios/usuariosService'

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
    },
    async toggleFavorito(moduloId, isFavorite) {
      if (!this.user?.id) return;
      try {
        await usuariosService.toggleFavorito(this.user.id, moduloId, isFavorite);
        // Actualizar localmente el estado de favorito
        const modulo = this.user.modulos.find(m => m.id == moduloId);
        if (modulo) {
          modulo.favorito = isFavorite ? 1 : 0;
        }
      } catch (e) {
        console.error("Error toggling favorite", e);
      }
    },

    async saveOrdenModulos(ordenes) {
      if (!this.user?.id) return;
      try {
        await usuariosService.reorderModulos(this.user.id, ordenes);
        // Reflejar el nuevo orden localmente en el store
        ordenes.forEach(({ id_modulo, orden }) => {
          const modulo = this.user.modulos.find(m => m.id == id_modulo);
          if (modulo) modulo.orden_usuario = orden;
        });
      } catch (e) {
        console.error("Error saving module order", e);
        throw e;
      }
    }
  },

  persist: {
    storage: sessionStorage,
  },
})
