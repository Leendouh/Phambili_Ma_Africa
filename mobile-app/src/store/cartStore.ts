import { create } from 'zustand';
import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';

const getStorageItem = async (key: string): Promise<string | null> => {
  if (Platform.OS === 'web') {
    return await AsyncStorage.getItem(key);
  }
  return await SecureStore.getItemAsync(key);
};

const setStorageItem = async (key: string, value: string): Promise<void> => {
  if (Platform.OS === 'web') {
    await AsyncStorage.setItem(key, value);
  } else {
    await SecureStore.setItemAsync(key, value);
  }
};

const deleteStorageItem = async (key: string): Promise<void> => {
  if (Platform.OS === 'web') {
    await AsyncStorage.removeItem(key);
  } else {
    await SecureStore.deleteItemAsync(key);
  }
};

export type CartItem = {
  id: number;
  name: string;
  price: number;
  quantity: number;
  image?: string | null;
};

interface CartState {
  items: CartItem[];
  totalQuantity: number;
  addItem: (item: { id: number; name: string; price: number | string; image?: string | null }) => void;
  updateQuantity: (id: number, quantity: number) => void;
  removeItem: (id: number) => void;
  clearCart: () => void;
  hydrate: () => Promise<void>;
}

export const useCartStore = create<CartState>((set, get) => ({
  items: [],
  totalQuantity: 0,

  addItem: async (item) => {
    const normalized = {
      id: Number(item.id),
      name: item.name || 'Product',
      price: Number(item.price ?? 0),
      image: item.image ?? null,
      quantity: 1,
    };

    const existing = get().items.find((cartItem) => cartItem.id === normalized.id);
    const nextItems = existing
      ? get().items.map((cartItem) =>
          cartItem.id === normalized.id
            ? { ...cartItem, quantity: cartItem.quantity + 1, price: normalized.price }
            : cartItem
        )
      : [...get().items, normalized];

    const nextTotalQuantity = nextItems.reduce((sum, cartItem) => sum + cartItem.quantity, 0);

    set({ items: nextItems, totalQuantity: nextTotalQuantity });
    await setStorageItem('cart_items', JSON.stringify(nextItems));
  },

  updateQuantity: async (id, quantity) => {
    const nextItems = get()
      .items.map((item) => (item.id === id ? { ...item, quantity: Math.max(0, quantity) } : item))
      .filter((item) => item.quantity > 0);

    const nextTotalQuantity = nextItems.reduce((sum, item) => sum + item.quantity, 0);
    set({ items: nextItems, totalQuantity: nextTotalQuantity });
    await setStorageItem('cart_items', JSON.stringify(nextItems));
  },

  removeItem: async (id) => {
    const nextItems = get().items.filter((item) => item.id !== id);
    const nextTotalQuantity = nextItems.reduce((sum, item) => sum + item.quantity, 0);
    set({ items: nextItems, totalQuantity: nextTotalQuantity });
    if (nextItems.length === 0) {
      await deleteStorageItem('cart_items');
      return;
    }
    await setStorageItem('cart_items', JSON.stringify(nextItems));
  },

  clearCart: async () => {
    set({ items: [], totalQuantity: 0 });
    await deleteStorageItem('cart_items');
  },

  hydrate: async () => {
    try {
      const stored = await getStorageItem('cart_items');
      if (!stored) {
        set({ items: [], totalQuantity: 0 });
        return;
      }

      const parsed = JSON.parse(stored);
      const items = Array.isArray(parsed) ? parsed : [];
      const totalQuantity = items.reduce((sum: number, item: CartItem) => sum + (Number(item.quantity) || 0), 0);
      set({ items, totalQuantity });
    } catch (error) {
      console.error('Failed to hydrate cart', error);
      set({ items: [], totalQuantity: 0 });
    }
  },
}));
