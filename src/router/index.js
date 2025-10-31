import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/stores/userStore'

import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'
import MenuView from '@/views/MenuView.vue'

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
    }
  ]
})

router.beforeEach((to, from, next) => {
    const userStore = useUserStore();
    const isLoggedIn = userStore.isLoggedIn;

    if (to.meta.requiresAuth && !isLoggedIn) {
        next('/login');
    } 
    else if (isLoggedIn && (to.name === 'login' || to.name === 'register')) {
        next('/menu');
    }
    else {
        next();
    }
})


export default router