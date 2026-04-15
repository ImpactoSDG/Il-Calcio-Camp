import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/userStore'

// --- Auth ---
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

// --- Layout ---
import MenuView from '@/views/MenuView.vue'
import SubmenuView from '@/views/SubmenuView.vue'

// --- Usuarios y Configuraciones ---
import UsuariosView from '@/views/usuarios/UsuariosView.vue'
import PermisosView from '@/views/usuarios/PermisosView.vue'
import GestionUsuariosView from '@/views/usuarios/GestionUsuariosView.vue'
import ConfiguracionesView from '@/views/usuarios/ConfiguracionesView.vue'

// --- Torneos (Gestión Deportiva) ---
import ArbitrosView from '@/views/torneos/ArbitrosView.vue'
import CalendarioTorneosView from '@/views/torneos/CalendarioTorneosView.vue'
import EquiposView from '@/views/torneos/EquiposView.vue'
import GestionTorneosView from '@/views/torneos/GestionTorneosView.vue'
import JugadoresView from '@/views/torneos/JugadoresView.vue'
import PlanTorneoView from '@/views/torneos/PlanTorneoView.vue'
import RtadoPartidoView from '@/views/torneos/RtadoPartidoView.vue'
import RtadoTorneoView from '@/views/torneos/RtadoTorneoView.vue'
import RegistroPublicoView from '@/views/torneos/RegistroPublicoView.vue'

// --- Instalaciones (Canchas y Eventos) ---
import CanchasView from '@/views/instalaciones/CanchasView.vue'
import GrillaCanchasView from '@/views/instalaciones/GrillaCanchasView.vue'
import EventosView from '@/views/instalaciones/EventosView.vue'

// --- Inventario (Stock y Proveedores) ---
import ArticulosView from '@/views/inventario/ArticulosView.vue'
import ArticulosVendidosView from '@/views/inventario/ArticulosVendidosView.vue'
import CategoriasArticuloView from '@/views/inventario/CategoriasArticuloView.vue'
import DescontarStockView from '@/views/inventario/DescontarStockView.vue'
import IngresoArticuloView from '@/views/inventario/IngresoArticuloView.vue'
import StockView from '@/views/inventario/StockView.vue'
import ProveedoresView from '@/views/inventario/ProveedoresView.vue'
import PedidosProveedorView from '@/views/inventario/PedidosProveedorView.vue'

// --- Comercial (Ventas y Clientes) ---
import ClientesView from '@/views/comercial/ClientesView.vue'
import ClienteEquipoView from '@/views/comercial/ClienteEquipoView.vue'
import CobroView from '@/views/comercial/CobroView.vue'
import VentasView from '@/views/comercial/VentasView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { 
      path: '/', 
      redirect: '/login'
    },
    { 
      path: '/inscripcion', 
      name: 'home',
      component: RegistroPublicoView
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
      path: '/submenu/:nombre',
      name: 'submenu',
      component: SubmenuView,
      meta: { requiresAuth: true, useParamName: true }
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
      path: '/articulos/vendidos',
      name: 'articulos-vendidos',
      component: ArticulosVendidosView,
      meta: { requiresAuth: true, idModulo: 32 }
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