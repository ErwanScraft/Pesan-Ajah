
/**
 * Data produk demo Pesan Ajah.
 *
 * Backend PHP + MySQL akan menjadi sumber data utama
 * setelah integrasi database diterapkan.
 */
export const defaultProducts = [
  {
    id: 1,
    name: "Beras Premium",
    unit: "1 kg",
    price: 16000,
    category: "Sembako",
    store: "Warung Sederhana",
    emoji: "🌾",
    tag: "Kebutuhan pokok",
    available: true,
  },
  {
    id: 2,
    name: "Telur Ayam",
    unit: "1 kg",
    price: 29000,
    category: "Sembako",
    store: "Warung Sederhana",
    emoji: "🥚",
    tag: "Pilihan warga",
    available: true,
  },
  {
    id: 3,
    name: "Minyak Goreng",
    unit: "1 liter",
    price: 19000,
    category: "Sembako",
    store: "Warung Sederhana",
    emoji: "🫗",
    tag: "",
    available: true,
  },
  {
    id: 4,
    name: "Mi Instan",
    unit: "1 bungkus",
    price: 3500,
    category: "Makanan",
    store: "Warung Sederhana",
    emoji: "🍜",
    tag: "",
    available: true,
  },
  {
    id: 5,
    name: "Air Mineral",
    unit: "600 ml",
    price: 4000,
    category: "Minuman",
    store: "Warung Sederhana",
    emoji: "💧",
    tag: "",
    available: true,
  },
  {
    id: 6,
    name: "Susu UHT",
    unit: "250 ml",
    price: 6000,
    category: "Minuman",
    store: "Warung Sederhana",
    emoji: "🥛",
    tag: "",
    available: false,
  },
  {
    id: 7,
    name: "Sabun Mandi",
    unit: "1 batang",
    price: 5000,
    category: "Kebutuhan Rumah",
    store: "Warung Sederhana",
    emoji: "🧼",
    tag: "",
    available: true,
  },
  {
    id: 8,
    name: "Gula Pasir",
    unit: "1 kg",
    price: 18000,
    category: "Sembako",
    store: "Warung Sederhana",
    emoji: "🧂",
    tag: "",
    available: true,
  },
  {
    id: 9,
    name: "Keripik Singkong",
    unit: "1 bungkus",
    price: 8000,
    category: "Makanan",
    store: "Warung Sederhana",
    emoji: "🥔",
    tag: "Camilan",
    available: true,
  },
  {
    id: 10,
    name: "Kopi Sachet",
    unit: "1 sachet",
    price: 2500,
    category: "Minuman",
    store: "Warung Sederhana",
    emoji: "☕",
    tag: "",
    available: true,
  },
  {
    id: 11,
    name: "Detergen",
    unit: "1 bungkus",
    price: 7000,
    category: "Kebutuhan Rumah",
    store: "Warung Sederhana",
    emoji: "🫧",
    tag: "",
    available: false,
  },
  {
    id: 12,
    name: "Permen",
    unit: "1 bungkus",
    price: 5000,
    category: "Lainnya",
    store: "Warung Sederhana",
    emoji: "🍬",
    tag: "",
    available: true,
  },
];

const STORAGE_KEY = "pesan-ajah-products";

function loadProducts() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);

    if (saved !== null) {
      const parsed = JSON.parse(saved);

      if (Array.isArray(parsed)) {
        return parsed;
      }
    }
  } catch (error) {
    console.error("Gagal membaca data produk:", error);
  }

  return defaultProducts.map((product) => ({ ...product }));
}

export const products = loadProducts();

export function saveProducts() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(products));
    return true;
  } catch (error) {
    console.error("Gagal menyimpan data produk:", error);
    return false;
  }
}
