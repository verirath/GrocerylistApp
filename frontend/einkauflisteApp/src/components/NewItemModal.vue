<script setup lang="ts">
import { ref } from "vue";
import type { NewItemPayload } from "../api/shoppingListApi";

const emit = defineEmits<{
  (e: "close"): void;
  (e: "submit", payload: NewItemPayload): void;
}>();

const item = ref<NewItemPayload>({
  name: "",
  quantity: 1,
  unit: "PCS",
  note: "",
});

const submitting = ref(false);
const errorMessage = ref<string | null>(null);

function handleClose() {
  if (submitting.value) return;
  emit("close");
}

async function handleSubmit() {
  if (!item.value.name.trim()) {
    errorMessage.value = "Bitte einen Produktnamen angeben.";
    return;
  }

  const payload: NewItemPayload = {
    name: item.value.name.trim(),
    quantity: item.value.quantity || 1,
    unit: item.value.unit,
    note: item.value.note?.trim() || undefined,
  };

  submitting.value = true;
  errorMessage.value = null;

  try {
    emit("submit", payload);
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="modal-backdrop" @click.self="handleClose">
    <div class="modal-card">
      <h2 class="modal-title">Neues Item</h2>

      <div class="items-section">
        <div class="items-header">
          <span>Item</span>
        </div>

        <div class="item-block">
          <div class="item-top-row">
            <input
              v-model="item.name"
              class="input item-name"
              type="text"
              placeholder="Produkt"
            />

            <input
              v-model.number="item.quantity"
              class="input item-qty"
              type="number"
              min="0.01"
              step="0.01"
            />

            <select v-model="item.unit" class="input item-unit">
              <option value="PCS">PCS</option>
              <option value="G">G</option>
              <option value="KG">KG</option>
              <option value="ML">ML</option>
              <option value="L">L</option>
              <option value="PACK">PACK</option>
            </select>
          </div>

          <input
            v-model="item.note"
            class="input item-note"
            placeholder="Notiz zum Item (optional)"
          />
        </div>
      </div>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

      <div class="actions">
        <button
          type="button"
          class="secondary"
          :disabled="submitting"
          @click="handleClose"
        >
          Abbrechen
        </button>
        <button
          type="button"
          class="primary"
          :disabled="submitting"
          @click="handleSubmit"
        >
          {{ submitting ? "Hinzufügen…" : "Item hinzufügen" }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 40;
}

.modal-card {
  width: 100%;
  max-width: 520px;
  background: #ffffff;
  border-radius: 20px;
  padding: 24px 24px 20px;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.35);
}

.modal-title {
  margin: 0 0 18px;
  font-size: 20px;
  font-weight: 600;
}

.items-section {
  margin-top: 8px;
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
  color: #4b5563;
  margin-bottom: 8px;
}

.input {
  border-radius: 10px;
  border: 1px solid #d1d5db;
  padding: 10px;
  margin: 5px 5px 5px 0;
  font-size: 15px;
  box-sizing: border-box;
}

.item-block {
  margin-bottom: 10px;
  padding: 10px 10px 12px;
  border-radius: 12px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
}

.item-top-row {
  display: grid;
  grid-template-columns: 1.6fr 0.8fr 0.9fr;
  gap: 6px;
  align-items: center;
}

.item-name {
  min-width: 0;
}

.item-qty {
  text-align: right;
}

.item-unit {
  min-width: 0;
}

.item-note {
  width: 100%;
  margin-top: 6px;
}

.error {
  color: #b91c1c;
  font-size: 14px;
  margin: 8px 0 0;
}

.actions {
  margin-top: 18px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.primary,
.secondary {
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 15px;
  border: none;
  cursor: pointer;
}

.primary {
  background: #385f63;
  color: white;
}

.primary:disabled {
  opacity: 0.7;
  cursor: default;
}

.secondary {
  background: #e5e7eb;
  color: #111827;
}

@media (max-width: 480px) {
  .modal-card {
    max-width: 100%;
    margin: 0 12px;
    padding: 20px 18px 16px;
  }

  .modal-title {
    font-size: 19px;
  }

  .input {
    font-size: 14px;
  }
}
</style>
