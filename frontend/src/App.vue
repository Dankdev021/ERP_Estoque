<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <h1>ERP Estoque</h1>
      <nav>
        <RouterLink to="/dashboard">Dashboard</RouterLink>
        <RouterLink to="/produtos">Produtos</RouterLink>
        <RouterLink to="/compras">Compras</RouterLink>
        <RouterLink to="/vendas">Vendas</RouterLink>
      </nav>
    </aside>
    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 240px 1fr;
}

.sidebar {
  background: var(--color-primary);
  color: #fff;
  padding: 24px 16px;
}

.sidebar h1 {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 20px;
}

.sidebar nav {
  display: grid;
  gap: 8px;
}

.sidebar a {
  color: #fff;
  text-decoration: none;
  padding: 10px 12px;
  border-radius: 8px;
  font-weight: 600;
}

.sidebar a.router-link-active {
  background: rgba(255, 255, 255, 0.2);
}

.content {
  padding: 24px;
  background: #fff;
}

@media (max-width: 900px) {
  .app-shell {
    grid-template-columns: 1fr;
  }

  .sidebar {
    padding: 16px;
  }

  .sidebar nav {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .content {
    padding: 16px;
  }
}
</style>
