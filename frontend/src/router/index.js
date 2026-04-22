import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import ProdutosView from '../views/ProdutosView.vue'
import ComprasView from '../views/ComprasView.vue'
import VendasView from '../views/VendasView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
    },
    {
      path: '/produtos',
      name: 'produtos',
      component: ProdutosView,
    },
    {
      path: '/compras',
      name: 'compras',
      component: ComprasView,
    },
    {
      path: '/vendas',
      name: 'vendas',
      component: VendasView,
    },
  ],
})

export default router
