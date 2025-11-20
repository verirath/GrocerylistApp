<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
  fetchShoppingLists,
  createShoppingList,
  type ShoppingListResponse,
  type CreateListPayload,
} from "../api/shoppingListApi";
import ShoppingListPreview from "../components/ShoppingListPreview.vue";
import NewListModal from "../components/NewListModal.vue";

const lists = ref<ShoppingListResponse[]>([]);
const showCreateModal = ref(false);
const loading = ref(false);
const loadError = ref<string | null>(null);

async function loadLists() {
  try {
    loading.value = true;
    loadError.value = null;
    const data = await fetchShoppingLists();
    lists.value = data;
  } catch (e: any) {
    console.error(e);
    loadError.value = "Listen konnten nicht geladen werden.";
  } finally {
    loading.value = false;
  }
}

async function handleCreateList(payload: CreateListPayload) {
  try {
    const newList = await createShoppingList(payload);
    lists.value.unshift(newList);
    showCreateModal.value = false;
  } catch (e: any) {
    console.error(e);
    alert("Liste konnte nicht angelegt werden. Details siehe Konsole.");
  }
}

onMounted(loadLists);
</script>

<template>
  <div class="page">
    <header class="header">
      Einkaufslistenbuddy
    </header>

    <div v-if="loading" class="status-text">Lade Einkaufslisten…</div>
    <div v-else-if="loadError" class="status-text error">
      {{ loadError }}
    </div>
    <div v-else class="lists">
      <ShoppingListPreview
        v-for="list in lists"
        :key="list.id"
        :list="list"
      />

      <p v-if="lists.length === 0" class="status-text">
        Noch keine Einkaufsliste – erstelle die erste mit dem + Button.
      </p>
    </div>

    <button class="fab" type="button" @click="showCreateModal = true">
      +
    </button>

    <NewListModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @submit="handleCreateList"
    />
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 16px 12px 24px;
  background: #f9fafb;
  display: flex;
  flex-direction: column;
}

.header {
  margin-bottom: 16px;
  font-size: 24px;
  font-weight: 600;
  text-align: center;
  font-family: Roboto Mono, Menlo, Consolas, monospace,
  "Segoe UI", sans-serif;
}

.lists {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.status-text {
  font-size: 14px;
  color: #6b7280;
}

.status-text.error {
  color: #b91c1c;
}

.fab {
  position: fixed;
  right: 20px;
  bottom: 20px;
  width: 56px;
  height: 56px;
  border-radius: 999px;
  border: none;
  background: #385f63;
  color: #ffffff;
  font-size: 30px;
  line-height: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 12px 30px rgb(69, 104, 108);
  cursor: pointer;
}

@media (min-width: 768px) {
  .page {
    max-width: 640px;
    margin: 0 auto;
    padding-top: 24px;
  }
}
</style>
