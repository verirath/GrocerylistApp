<script setup lang="ts">
defineProps({
  list: { type: Object, required: true }
});
</script>

<template>
  <div class="card">
    <h2 class="card-title">{{ list.name }}</h2>

    <ul class="item-list">
      <li
        v-for="item in list.items"
        :key="item.id"
        class="item-row"
      >
        <span class="item-name">{{ item.name }}</span>
        <span class="item-qty">{{ item.quantity }} {{ item.unit }}</span>
      </li>
    </ul>

    <div v-if="list.items.length > 3" class="more-hint">
      … und {{ list.items.length - 3 }} weitere
    </div>
  </div>
</template>

<style scoped>
.card {
  background: #ffffff;
  border-radius: 16px;
  padding: 14px 18px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 6px 0;
  color: #385f63;
  font-family: "Roboto", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI",
  sans-serif;
}

.item-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  font-size: 15px;
}

.item-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding-right: 8px;
}

.item-qty {
  color: #111827;
  font-weight: 500;
  white-space: nowrap;
}

.more-hint {
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
}

@media (max-width: 600px) {
  .card {
    padding: 16px 18px;
    border-radius: 20px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
  }

  .card-title {
    font-size: 20px;
    color: #385f63;
  }

  .item-row {
    font-size: 16px;
  }
}
</style>
