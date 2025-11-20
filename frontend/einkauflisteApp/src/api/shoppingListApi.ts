export interface ShoppingListItem {
  id: number;
  name: string;
  quantity: number;
  unit: string | null;
}

export interface ShoppingListResponse{
  id: number;
  name: string;
  items: ShoppingListItem[];
}

const BASE_URL = "http://127.0.0.1:8000";

export async function fetchShoppingLists(): Promise<ShoppingListResponse[]> {
  const response = await fetch(`${BASE_URL}/lists`);

  if (!response.ok) {
    throw new Error("Failed to fetch shopping lists");
  }

  return await response.json();
}
