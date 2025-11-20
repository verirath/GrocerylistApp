<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  fetchShoppingLists,
  type ShoppingListResponse,
} from "../api/shoppingListApi";
import ShoppingListPreview from "../components/ShoppingListPreview.vue";

const lists = ref<ShoppingListResponse[]>([]);

async function loadLists() {
  try {
    const data = await fetchShoppingLists();
    lists.value = data;
  } catch (e) {
    console.error(e);
  }
}

onMounted(loadLists);
</script>

<template>
  <div class="page">
    <header class="header">
      Einkaufslistenbuddy
    </header>

    <div class="lists">
      <ShoppingListPreview
        v-for="list in lists"
        :key="list.id"
        :list="list"
      />
    </div>
  </div>
</template>

<style scoped>
.page {
  max-width: 900px;
  margin: 0 auto;
  padding: 24px 16px;
}

.header {
  margin-bottom: 30px;
  font-size: 32px;
  font-weight: 600;
  text-align: center;
  font-family: Roboto Mono, Menlo, Consolas, monospace;
}

.lists {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

@media (max-width: 600px) {
  .page {
    padding: 16px 12px 24px;
  }

  .header {
    font-size: 24px;
    margin-bottom: 16px;
  }
}
</style>
