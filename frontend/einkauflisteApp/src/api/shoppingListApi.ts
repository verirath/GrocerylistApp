export interface ShoppingListItem {
  id: number;
  name: string;
  quantity: number;
  unit: string;
  note?: string | null;
  is_checked: boolean;
  created_at: string;
  updated_at?: string | null;
}

export interface ShoppingListResponse {
  id: number;
  name: string;
  created_at: string;
  updated_at: string | null;
  items: ShoppingListItem[];
}

export interface NewItemPayload {
  name: string;
  quantity: number;
  unit: string;
  note?: string;
}

export interface CreateListPayload {
  name: string;
  items?: NewItemPayload[];
}

export interface UpdateItemPayload {
  name: string;
  quantity: number;
  unit: string;
  note?: string | null;
  is_checked: boolean;
}

export interface CreateItemPayload {
  id: number;
  name: string;
  quantity: number;
  unit: string;
  note?: string | null;
  is_checked: boolean;
  created_at: string;
  updated_at?: string | null;
}

const API_BASE = "http://127.0.0.1:8000";

export async function fetchShoppingLists(): Promise<ShoppingListResponse[]> {
  const res = await fetch(`${API_BASE}/lists`);
  if (!res.ok) {
    throw new Error("Failed to fetch shopping lists");
  }
  return await res.json();
}

export async function createShoppingList(
  payload: CreateListPayload
): Promise<ShoppingListResponse> {
  const res = await fetch(`${API_BASE}/lists`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Failed to create list: ${res.status} ${text}`);
  }

  return await res.json();
}

export async function fetchShoppingList(id: number): Promise<ShoppingListResponse> {
  const res = await fetch(`${API_BASE}/lists/${id}/items`, {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (!res.ok) {
    throw new Error("Failed to fetch shopping list");
  }

  return await res.json();
}

export async function updateItem(
  listId: number,
  itemId: number,
  payload: UpdateItemPayload
): Promise<ShoppingListItem> {
  const res = await fetch(`${API_BASE}/lists/${listId}/items/${itemId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`Failed to update item: ${res.status} ${text}`);
  }

  return await res.json();
}
