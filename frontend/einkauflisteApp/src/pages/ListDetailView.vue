<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  fetchShoppingList,
  type ShoppingListResponse,
} from "../api/shoppingListApi";

const route = useRoute();
const router = useRouter();

const listId = computed(() => Number(route.params.id));

const list = ref<ShoppingListResponse | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const editMode = ref(false);

async function loadList() {
  try {
    loading.value = true;
    error.value = null;

    const data = await fetchShoppingList(listId.value);
    list.value = data;
  } catch (e) {
    console.error(e);
    error.value = "Die Einkaufsliste konnte nicht geladen werden.";
  } finally {
    loading.value = false;
  }
}

function toggleMode() {
  editMode.value = !editMode.value;
}

function toggleItemChecked(item: ShoppingListResponse["items"][number]) {
  item.is_checked = !item.is_checked;
  // TODO: PUT /lists/{listId}/items/{itemId} um is_checked in der DB zu speichern
}

function handleBack() {
  router.back();
}

function handleAddItem() {
  console.log("TODO: neues Item im Detail-View hinzufügen");
}

onMounted(loadList);
</script>

<template>
  <div class="page">
    <header class="header">
      Einkaufslistenbuddy
    </header>

    <main class="content">
      <div class="top-bar">
        <button class="back-btn" type="button" @click="handleBack">
          ‹ Zurück
        </button>

        <div class="mode-switch">
          <span class="mode-label" :class="{ active: !editMode }">
            Einkaufen
          </span>

          <button
            type="button"
            class="mode-track"
            @click="toggleMode"
            aria-label="Ansicht wechseln"
          >
            <span class="mode-thumb" :class="{ right: editMode }">
              <span class="mode-thumb-icon">
                {{ editMode ? "✏️" : "🛒" }}
              </span>
            </span>
          </button>

          <span class="mode-label" :class="{ active: editMode }">
            Bearbeiten
          </span>
        </div>
      </div>

      <div v-if="loading" class="state-text">
        Lädt …
      </div>

      <div v-else-if="error" class="state-text state-error">
        {{ error }}
      </div>

      <section v-else-if="list" class="detail-card">
        <div class="detail-header">
          <h1 class="list-title">
            {{ list.name }}
          </h1>
        </div>

        <p class="meta">
          {{ list.items.length }} Artikel
        </p>

        <ul class="items">
          <li
            v-for="item in list.items"
            :key="item.id"
            class="item-row"
            :class="{ checked: item.is_checked }"
          >
            <template v-if="!editMode">
              <label class="item-main">
                <input
                  class="item-checkbox"
                  type="checkbox"
                  :checked="item.is_checked"
                  @change="toggleItemChecked(item)"
                />
                <div class="item-text">
                  <span class="item-name">{{ item.name }}</span>
                  <span v-if="item.note" class="item-note">
                    {{ item.note }}
                  </span>
                </div>
              </label>

              <span class="item-qty">
                {{ item.quantity }} {{ item.unit }}
              </span>
            </template>

            <template v-else>
              <div class="item-main edit-placeholder">
                <div class="item-text">
                  <span class="item-name">{{ item.name }}</span>
                  <span v-if="item.note" class="item-note">
                    {{ item.note }}
                  </span>
                </div>
              </div>
              <span class="item-qty">
                {{ item.quantity }} {{ item.unit }}
              </span>
            </template>
          </li>
        </ul>
      </section>

      <button
        v-if="list && editMode"
        class="fab"
        type="button"
        @click="handleAddItem"
      >
        +
      </button>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 16px 12px 24px;
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

.content {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.back-btn {
  border: none;
  background: transparent;
  color: #4b5563;
  font-size: 14px;
  cursor: pointer;
  padding: 4px 0;
}

.mode-switch {
  display: flex;
  align-items: center;
  gap: 8px;
}

.mode-label {
  font-size: 13px;
  color: #9ca3af;
  min-width: 72px;
  text-align: center;
}

.mode-label.active {
  color: #111827;
  font-weight: 500;
}

.mode-track {
  position: relative;
  width: 90px;
  height: 36px;
  border-radius: 999px;
  border: none;
  padding: 0;
  background: #dbeafe;
  cursor: pointer;
}

.mode-thumb {
  position: absolute;
  top: 4px;
  left: 4px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #ffffff;
  box-shadow: 0 4px 10px rgba(15, 23, 42, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease;
}

.mode-thumb.right {
  transform: translateX(48px);
}

.mode-thumb-icon {
  font-size: 18px;
}

.state-text {
  padding: 24px 0;
  text-align: center;
  color: #6b7280;
}

.state-error {
  color: #b91c1c;
}

.detail-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px 14px 18px;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
  border: 1px solid #e5e7eb;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.list-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.meta {
  margin: 6px 0 10px;
  font-size: 13px;
  color: #6b7280;
}

.items {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 10px;
  border-radius: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  gap: 8px;
}

.item-row.checked {
  opacity: 0.6;
}

.item-main {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex: 1;
}

.item-checkbox {
  margin-top: 2px;
}

.item-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-name {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
}

.item-note {
  font-size: 13px;
  color: #6b7280;
}

.item-qty {
  white-space: nowrap;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.edit-placeholder {
  opacity: 0.8;
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
