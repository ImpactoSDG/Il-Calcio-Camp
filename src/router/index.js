import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import MenuView from '@/views/MenuView.vue'
import UsuariosView from '@/views/UsuariosView.vue'
import PermisosView from '@/views/PermisosView.vue'
import GestionUsuariosView from '@/views/GestionUsuariosView.vue'
import ConfiguracionesView from '../views/ConfiguracionesView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { 
      path: '/', 
      name: 'home',
      redirect: '/login' 
    },
    { 
      path: '/login', 
      name: 'login', 
      component: LoginView 
    },
    { 
      path: '/register', 
      name: 'register', 
      component: RegisterView 
    },
    { 
      path: '/menu', 
      name: 'menu', 
      component: MenuView, 
      meta: { requiresAuth: true } 
    },
    { 
      path: '/principal', 
      name: 'principal', 
      component: MenuView, 
      meta: { requiresAuth: true, idModulo: 1 } 
    },
    { 
      /* MÓDULO PADRE: Usuarios */
      path: '/usuarios', 
      name: 'usuarios', 
      component: UsuariosView, 
      meta: { requiresAuth: true, idModulo: 2 } 
    },
    { 
      path: '/permisos', 
      name: 'permisos', 
      component: PermisosView, 
      meta: { requiresAuth: true, idModulo: 4 } 
    },
    {
      path: '/gestion',
      name: 'gestion',
      component: GestionUsuariosView,
      meta: { requiresAuth: true, idModulo: 3 }
    },
    { 
      path: '/configuraciones',
      name: 'configuraciones',
      component: ConfiguracionesView,
      meta: { requiresAuth: true, idModulo: 5 }
    }
  ]
})

router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  const isLoggedIn = userStore.isLoggedIn

  if (to.meta.requiresAuth && !isLoggedIn) {
    return next('/login')
  }

  if (isLoggedIn && (to.name === 'login' || to.name === 'register')) {
    return next('/menu')
  }

  if (to.meta.idModulo) {
    const tienePermiso = userStore.user?.modulos?.some(
      m => Number(m.id) === Number(to.meta.idModulo)
    )

    if (!tienePermiso) {
      alert('No tienes acceso a este módulo')
      return next('/menu')
    }
  }

  next()
})

export default router