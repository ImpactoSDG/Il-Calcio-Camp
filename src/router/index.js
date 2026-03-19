import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/userStore'

// --- Vistas Base ---
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import MenuView from '@/views/MenuView.vue'
import PermisosView from '@/views/PermisosView.vue'
import GestionUsuariosView from '@/views/GestionUsuariosView.vue'
import ConfiguracionesView from '@/views/ConfiguracionesView.vue'
import SubmenuView from '@/views/SubmenuView.vue'

// --- Vistas de ArtÃ­culos y Stock ---
import CategoriasArticuloView from '@/views/CategoriasArticuloView.vue'
import ArticulosView from '@/views/ArticulosView.vue'
import IngresoArticuloView from '@/views/IngresoArticuloView.vue'
import StockView from '@/views/StockView.vue'
import DescontarStockView from '@/views/DescontarStockView.vue'

// --- Vistas Deportivas (Equipos, Torneos, etc.) ---
import EquiposView from '@/views/EquiposView.vue'
import JugadoresView from '@/views/JugadoresView.vue'
import CanchasView from '@/views/CanchasView.vue'
import ArbitrosView from '@/views/ArbitrosView.vue'
import EventosView from '@/views/EventosView.vue'
import PlanTorneoView from '@/views/PlanTorneoView.vue'
import GestionTorneosView from '@/views/GestionTorneosView.vue'
import CalendarioTorneosView from '@/views/CalendarioTorneosView.vue'
import RtadoPartidoView from '@/views/RtadoPartidoView.vue'
import RtadoTorneoView from '@/views/RtadoTorneoView.vue'
import GrillaCanchasView from '@/views/GrillaCanchasView.vue'

// --- Vistas de Clientes, Ventas y Cobros ---
import ClientesView from '@/views/ClientesView.vue'
import ClienteEquipoView from '@/views/ClienteEquipoView.vue'
import VentasView from '@/views/VentasView.vue'
import CobroView from '@/views/CobroView.vue'

// --- Vistas de Proveedores y Compras ---
import ProveedoresView from '@/views/ProveedoresView.vue'
import PedidosProveedorView from '@/views/PedidosProveedorView.vue'

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

    // --- ArtÃ­culos & Stock ---
    {
      path: '/categorias-articulo',
      name: 'categorias-articulo',
      component: CategoriasArticuloView,
      meta: { requiresAuth: true, idModulo: 9 }
    },
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
    {
      path: '/articulos/descontar-stock',
      name: 'descontar-stock',
      component: DescontarStockView,
      meta: { requiresAuth: true, idModulo: 11 } 
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
    {
      path: '/equipos',
      name: 'equipos',
      component: EquiposView,
      meta: { requiresAuth: true, idModulo: 10 }
    },

    // --- GestiÃ³n Deportiva (Torneos, Jugadores, etc.) ---
    {
      path: '/jugadores',
      name: 'jugadores',
      component: JugadoresView,
      meta: { requiresAuth: true }
    },
    {
      path: '/canchas',
      name: 'canchas',
      component: CanchasView,
      meta: { requiresAuth: true }
    },
    {
      path: '/arbitros',
      name: 'arbitros',
      component: ArbitrosView,
      meta: { requiresAuth: true }
    },
    {
      path: '/eventos',
      name: 'eventos',
      component: EventosView,
      meta: { requiresAuth: true }
    },
    {
      path: '/plantorneo',
      name: 'plantorneo',
      component: PlanTorneoView,
      meta: { requiresAuth: true, idModulo: 22 }
    },
    {
      path: '/gestiontorneos',
      name: 'gestiontorneos',
      component: GestionTorneosView,
      meta: { requiresAuth: true }
    },
    {
      path: '/calendario-torneos',
      name: 'calendario-torneos',
      component: CalendarioTorneosView,
      meta: { requiresAuth: true }
    },
    {
      path: '/rtadopartido',
      name: 'rtadopartido',
      component: RtadoPartidoView,
      meta: { requiresAuth: true }
    },
    {
      path: '/rtadotorneo',
      name: 'rtadotorneo',
      component: RtadoTorneoView,
      meta: { requiresAuth: true }
    },
    {
      path: '/grillacanchas',
      name: 'grillacanchas',
      component: GrillaCanchasView,
      meta: { requiresAuth: true }
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

    // --- Compras / Proveedores ---
    {
      path: '/proveedores',
      name: 'proveedores',
      component: ProveedoresView,
      meta: { requiresAuth: true , idModulo: 25}
    },
    {
      path: '/compras/pedidos',
      name: 'pedidos-proveedor',
      component: PedidosProveedorView,
      meta: { requiresAuth: true , idModulo: 26}
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
      alert('No tienes acceso a este mÃ³dulo')
      return next('/menu')
    }
  }

  next()
})

export default router