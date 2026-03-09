import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import MenuView from '@/views/MenuView.vue'
import PermisosView from '@/views/PermisosView.vue'
import GestionUsuariosView from '@/views/GestionUsuariosView.vue'
import ConfiguracionesView from '@/views/ConfiguracionesView.vue'
import SubmenuView from '@/views/SubmenuView.vue'
import CategoriasArticuloView from '@/views/CategoriasArticuloView.vue'
import EquiposView from '@/views/EquiposView.vue'
import ArticulosView from '@/views/ArticulosView.vue'
import IngresoArticuloView from '@/views/IngresoArticuloView.vue'
import StockView from '@/views/articulos/StockView.vue'
import ClientesView from '@/views/ClientesView.vue'
import ClienteEquipoView from '@/views/ClienteEquipoView.vue'
import VentasView from '@/views/ventas/VentasView.vue'
import CobroView from '@/views/CobroView.vue'

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
    },
    {
      path: '/submenu/:id',
      name: 'submenu',
      component: SubmenuView,
      meta: { requiresAuth: true, useParamId: true }
    },

    // --- Datos Maestros ---
    {
      path: '/datos-maestros/categorias-articulo',
      name: 'categorias-articulo',
      component: CategoriasArticuloView,
      meta: { requiresAuth: true, idModulo: 9 }
    },
    {
      path: '/datos-maestros/equipos',
      name: 'equipos',
      component: EquiposView,
      meta: { requiresAuth: true, idModulo: 10 }
    },

    // --- Artículos & Stock ---
    {
      path: '/articulos',
      name: 'articulos',
      component: ArticulosView,
      meta: { requiresAuth: true, idModulo: 11 }
    },
    {
      path: '/articulos/ingresos',
      name: 'ingresos-articulo',
      component: IngresoArticuloView,
      meta: { requiresAuth: true, idModulo: 12 }
    },
    {
      path: '/articulos/stock',
      name: 'stock',
      component: StockView,
      meta: { requiresAuth: true, idModulo: 16 }
    },

    // --- Clientes & Equipos ---
    {
      path: '/clientes',
      name: 'clientes',
      component: ClientesView,
      meta: { requiresAuth: true, idModulo: 13 }
    },
    {
      path: '/clientes/equipos',
      name: 'cliente-equipo',
      component: ClienteEquipoView,
      meta: { requiresAuth: true, idModulo: 14 }
    },

    // --- Ventas ---
    {
      path: '/ventas',
      name: 'ventas',
      component: VentasView,
      meta: { requiresAuth: true, idModulo: 15 }
    },

    // --- Cobros ---
    {
      path: '/cobros',
      name: 'cobros',
      component: CobroView,
      meta: { requiresAuth: true }
    },
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

  const idModulo = to.meta.useParamId ? to.params.id : to.meta.idModulo
  if (idModulo) {
    const tienePermiso = userStore.user?.modulos?.some(
      m => Number(m.id) === Number(idModulo)
    )

    if (!tienePermiso) {
      alert('No tienes acceso a este módulo')
      return next('/menu')
    }
  }

  next()
})

export default router