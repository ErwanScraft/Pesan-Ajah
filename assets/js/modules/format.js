
const rupiahFormatter = new Intl.NumberFormat("id-ID", {
  style: "currency",
  currency: "IDR",
  maximumFractionDigits: 0,
});

export function formatRupiah(amount) {
  const value = Number(amount);

  if (!Number.isFinite(value)) {
    return rupiahFormatter.format(0);
  }

  return rupiahFormatter.format(value);
}
