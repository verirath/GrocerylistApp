<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  fetchShoppingList,
  updateItem,
  createItem,
  deleteItem,
  deleteList,
  type ShoppingListResponse,
  type NewItemPayload,
} from "../api/shoppingListApi";
import NewItemModal from "../components/NewItemModal.vue";

const route = useRoute();
const router = useRouter();

const listId = computed(() => Number(route.params.id));

const list = ref<ShoppingListResponse | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const editMode = ref(false);
const draftList = ref<ShoppingListResponse | null>(null);
const saving = ref(false);
const saveError = ref<string | null>(null);

const showNewItemModal = ref(false);
const addingItems = ref(false);

const activeList = computed<ShoppingListResponse | null>(() =>
  editMode.value && draftList.value ? draftList.value : list.value
);

async function loadList() {
  try {
    loading.value = true;
    error.value = null;

    const data = await fetchShoppingList(listId.value);
    list.value = data;

    if (editMode.value) {
      draftList.value = JSON.parse(JSON.stringify(data));
    }
  } catch (e) {
    console.error(e);
    error.value = "Die Einkaufsliste konnte nicht geladen werden.";
  } finally {
    loading.value = false;
  }
}

async function toggleItemChecked(item: ShoppingListResponse["items"][number]) {
  if (!list.value) return;

  const original = item.is_checked;
  item.is_checked = !original;

  try {
    await updateItem(list.value.id, item.id, {
      name: item.name,
      quantity: item.quantity,
      unit: item.unit,
      note: item.note ?? null,
      is_checked: item.is_checked,
    });
  } catch (err) {
    console.error(err);
    item.is_checked = original;
  }
}

function enterEditMode() {
  if (!list.value) return;
  draftList.value = JSON.parse(JSON.stringify(list.value));
  editMode.value = true;
  saveError.value = null;
}

function cancelEdit() {
  editMode.value = false;
  draftList.value = null;
  saving.value = false;
  saveError.value = null;
}

async function saveEdit() {
  if (!draftList.value || !list.value) return;

  saving.value = true;
  saveError.value = null;

  try {
    const promises = draftList.value.items.map((item) =>
      updateItem(list.value!.id, item.id, {
        name: item.name,
        quantity: item.quantity,
        unit: item.unit,
        note: item.note ?? null,
        is_checked: item.is_checked,
      })
    );

    const updatedItems = await Promise.all(promises);

    list.value = {
      ...draftList.value,
      items: updatedItems,
    };

    editMode.value = false;
    draftList.value = null;
  } catch (e) {
    console.error(e);
    saveError.value = "Änderungen konnten nicht gespeichert werden.";
  } finally {
    saving.value = false;
  }
}

function handleAddItemFab() {
  showNewItemModal.value = true;
}

async function handleAddItems(payload: NewItemPayload[]) {
  if (!activeList.value) return;

  addingItems.value = true;
  saveError.value = null;

  try {
    for (const newItem of payload) {
      const created = await createItem(activeList.value.id, newItem);

      if (draftList.value) {
        draftList.value.items.push(created);
      }
      if (list.value) {
        list.value.items.push(JSON.parse(JSON.stringify(created)));
      }
    }

    showNewItemModal.value = false;
  } catch (e) {
    console.error(e);
    saveError.value = "Neue Items konnten nicht angelegt werden.";
  } finally {
    addingItems.value = false;
  }
}

async function handleDeleteItem(itemId: number) {
  if (!activeList.value) return;

  const ok = window.confirm("Dieses Item wirklich löschen?");
  if (!ok) return;

  try {
    await deleteItem(activeList.value.id, itemId);

    if (draftList.value) {
      draftList.value.items = draftList.value.items.filter(
        (item) => item.id !== itemId
      );
    }
    if (list.value) {
      list.value.items = list.value.items.filter(
        (item) => item.id !== itemId
      );
    }
  } catch (e) {
    console.error(e);
    saveError.value = "Item konnte nicht gelöscht werden.";
  }
}

async function handleDeleteList() {
  if (!list.value) return;

  const ok = window.confirm("Diese Einkaufsliste wirklich löschen?");
  if (!ok) return;

  try {
    await deleteList(list.value.id);
    router.push({ name: "home" });
  } catch (e) {
    console.error(e);
    saveError.value = "Liste konnte nicht gelöscht werden.";
  }
}

function handleBack() {
  router.back();
}

onMounted(loadList);
</script>

<template>
  <div class="page">
    <router-link to="/" class="header">
      Einkaufslistenbuddy
    </router-link>

    <main class="content">
      <div class="top-row">
        <button class="back-btn" type="button" @click="handleBack">
          ‹ Zurück
        </button>

        <div class="mode-toggle-wrapper">
          <span :class="['mode-label', !editMode && 'active']">🛒</span>
          <button
            type="button"
            class="mode-pill"
            @click="editMode ? cancelEdit() : enterEditMode()"
          >
            <span :class="['mode-thumb', editMode && 'right']"></span>
          </button>
          <span :class="['mode-label', editMode && 'active']">✏️</span>
        </div>
      </div>

      <div v-if="loading" class="state-text">
        Lädt …
      </div>

      <div v-else-if="error" class="state-text state-error">
        {{ error }}
      </div>

      <section v-else-if="activeList" class="detail-card">
        <div class="detail-header">
          <h1 class="list-title">
            <template v-if="editMode">
              <input v-model="activeList.name" class="title-input" />
            </template>
            <template v-else>
              {{ activeList.name }}
            </template>
          </h1>
        </div>

        <p class="meta">
          {{ activeList.items.length }} Artikel
        </p>

        <ul class="items">
          <li
            v-for="item in activeList.items"
            :key="item.id"
            class="item-row"
            :class="{ checked: !editMode && item.is_checked }"
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
              <div class="item-main edit-main">
                <div class="item-text">
                  <input
                    v-model="item.name"
                    class="item-input name-input"
                    type="text"
                  />
                  <input
                    v-model="item.note"
                    class="item-input note-input"
                    placeholder="Notiz (optional)"
                  />
                </div>
              </div>

              <div class="item-qty-edit">
                <input
                  v-model.number="item.quantity"
                  class="item-input qty-input"
                  type="number"
                  min="0.01"
                  step="0.01"
                />
                <select v-model="item.unit" class="item-input unit-input">
                  <option value="PCS">PCS</option>
                  <option value="G">G</option>
                  <option value="KG">KG</option>
                  <option value="ML">ML</option>
                  <option value="L">L</option>
                  <option value="PACK">PACK</option>
                </select>

                <button
                  type="button"
                  class="item-delete-btn"
                  @click="handleDeleteItem(item.id)"
                >
                  🗑
                </button>
              </div>
            </template>
          </li>
        </ul>

        <p v-if="saveError" class="state-text state-error small">
          {{ saveError }}
        </p>

        <div v-if="editMode" class="edit-actions">

          <div class="left-actions">
            <button
              type="button"
              class="delete-list-btn"
              @click="handleDeleteList"
            >
              Liste löschen
            </button>
          </div>

          <div class="right-actions">
            <button
              type="button"
              class="secondary"
              :disabled="saving"
              @click="cancelEdit"
            >
              Abbrechen
            </button>
            <button
              type="button"
              class="primary"
              :disabled="saving"
              @click="saveEdit"
            >
              {{ saving ? "Speichern…" : "Speichern" }}
            </button>
          </div>

        </div>

      </section>

      <button
        v-if="activeList && editMode"
        class="fab"
        type="button"
        :disabled="addingItems"
        @click="handleAddItemFab"
      >
        +
      </button>

      <NewItemModal
        v-if="activeList && editMode && showNewItemModal"
        @close="showNewItemModal = false"
        @submit="handleAddItems"
      />
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
  font-family: Roboto Mono, Menlo, Consolas, monospace, "Segoe UI", sans-serif;
  text-decoration: none;
  display: block;
  cursor: pointer;
  color: black;
}

.content {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.top-row {
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

.mode-toggle-wrapper {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.mode-label {
  color: #9ca3af;
}

.mode-label.active {
  color: #111827;
  font-weight: 500;
}

.mode-pill {
  position: relative;
  width: 44px;
  height: 22px;
  border-radius: 999px;
  border: none;
  background: #e0f2fe;
  padding: 0;
  cursor: pointer;
}

.mode-thumb {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 16px;
  height: 16px;
  border-radius: 999px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.3);
  transition: transform 0.18s ease;
}

.mode-thumb.right {
  transform: translateX(20px);
}

.state-text {
  padding: 24px 0;
  text-align: center;
  color: #6b7280;
}

.state-text.small {
  padding: 8px 0 0;
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

.title-input {
  width: 100%;
  border-radius: 10px;
  border: 1px solid #d1d5db;
  padding: 6px 8px;
  font-size: 16px;
  font-weight: 600;
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
  align-items: stretch;
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

.edit-main {
  align-items: stretch;
}

.item-input {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  padding: 6px 8px;
  font-size: 14px;
  width: 100%;
  box-sizing: border-box;
}

.note-input {
  margin-top: 4px;
}

.item-qty-edit {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 110px;
  align-items: flex-end;
}

.qty-input {
  text-align: right;
}

.unit-input {
  padding-right: 24px;
}

.item-delete-btn {
  padding-top: 6px;
  border: none;
  background: transparent;
  color: #991b1b;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
}

.edit-actions {
  margin-top: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-actions,
.right-actions {
  display: flex;
  gap: 8px;
}

.primary,
.secondary {
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 14px;
  border: none;
  cursor: pointer;
}

.primary {
  background: #385f63;
  color: #ffffff;
}

.primary:disabled {
  opacity: 0.7;
  cursor: default;
}

.secondary {
  background: #e5e7eb;
  color: #111827;
}

.delete-list-row {
  margin-top: 10px;
  display: flex;
  justify-content: flex-start;
}

.delete-list-btn {
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 14px;
  border: none;
  cursor: pointer;
  background-color: rgba(207, 17, 17, 0.92);
  color: white;
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
  box-shadow: 0 12px 30px rgb(69 104 108 / 0.8);
  cursor: pointer;
}

.fab:disabled {
  opacity: 0.7;
  cursor: default;
}

.list-link {

}

@media (min-width: 768px) {
  .page {
    max-width: 640px;
    margin: 0 auto;
    padding-top: 24px;
  }

  .header {
    font-size: 24px;
  }
}
</style>
