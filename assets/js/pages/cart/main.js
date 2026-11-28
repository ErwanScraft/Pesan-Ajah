import { createBottomSheet } from "../../modules/bottom-sheet.js";
import { createGps } from "../../modules/components/gps.js";

export function initCartPage({
  products,
  cart,
  showToast,
  formatRupiah,
  onCatalogRender,
}) {
  const $ = (selector) => document.querySelector(selector);

  const toast = $("#toast");
  const cartOverlay = $("#cart-overlay");
  const cartButton = $("#cart-trigger");
  const closeCartButton = $("#close-cart");
  const cartItems = $("#cart-items");
  const cartCountItems = $("#cart-count-items");
  const checkoutButton = $("#checkout-button");
  const cartSheet = createBottomSheet({
    independent: true,
  });
  const checkoutSheet = createBottomSheet({
    independent: true,
  });
  const cartFooter = $("#cart-footer");
  const confirmationPanel = $("#cart-confirmation");
  const successPanel = $("#cart-order-success");
  const confirmationItems = $("#confirmation-items");

  const fulfillmentInputs = document.querySelectorAll(
    'input[name="fulfillment-method"]',
  );
  
  const gps = createGps({
      button: $("#use-location-button"),
      errorElement: $("#delivery-gps-error"),
      latitudeInput: $("#delivery-latitude"),
      longitudeInput: $("#delivery-longitude"),
      mapsUrlInput: $("#delivery-maps-url"),
  
      onSuccess: (location) => {
        renderCart();
  
        showToast(
          toast,
          "Lokasi GPS berhasil diperoleh.",
        );
  
        console.info(
          "[Pesan Ajah] GPS berhasil:",
          {
            accuracy: location.accuracy,
          },
        );
      },
  
      onError: (error) => {
        showToast(
          toast,
        error.gpsMessage || error.message,
      );

      console.warn(
        "[Pesan Ajah] GPS gagal:",
        error,
      );
    },
  });


  const cartView = {
    count: $("#cart-count"),
    list: cartItems,
    empty: $("#cart-empty"),
    footer: $("#cart-footer"),
    totalLabel: $("#cart-subtotal"),
  };

  let csrfToken = "";
  let catalogLoaded = false;
  let deliveryAvailable = null;
  let activeStoreId = null;
  let activeCartStage = "selection";
  let openCheckoutAfterCartClose = false;
  let reopenCartAfterCheckoutClose = false;
  
  async function loadCsrfToken() {
    const response = await fetch("/api/csrf.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    });

    let result;

    try {
      result = await response.json();
    } catch {
      throw new Error(
        "Gagal mengambil token keamanan. Muat ulang halaman.",
      );
    }

    if (
      !response.ok ||
      !result.success ||
      typeof result.csrf_token !== "string" ||
      !result.csrf_token
    ) {
      throw new Error(
        result.message || "Token keamanan tidak tersedia. Coba kembali.",
      );
    }

    csrfToken = result.csrf_token;

    return csrfToken;
  }
  
  async function sendCheckoutRequest(payload) {
    for (let attempt = 0; attempt < 2; attempt++) {
      if (!csrfToken) {
        await loadCsrfToken();
      }

      payload._token = csrfToken;

      const response = await fetch("/api/checkout.php", {
        method: "POST",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      let result;

      try {
        result = await response.json();
      } catch {
        throw new Error(
          "Respons server tidak valid. Silakan coba kembali.",
        );
      }

      // Token kedaluwarsa atau tidak cocok: ambil token baru.
      if (response.status === 419 && attempt === 0) {
        csrfToken = "";
        await loadCsrfToken();
        continue;
      }

      if (!response.ok || !result.success) {
        throw new Error(
          result.message || "Checkout gagal diproses.",
        );
      }

      return result;
    }

    throw new Error(
      "Sesi checkout tidak valid. Muat ulang halaman.",
    );
  }

  function selectedFulfillment() {
    return document.querySelector(
      'input[name="fulfillment-method"]:checked',
    )?.value ?? "delivery";
  }

  function selectedStore() {
    const entries = cart.entries(activeStoreId);
  
    if (!entries.length) return null;
  
    return products.find(
      (product) =>
        Number(product.id) === Number(entries[0].product_id),
    ) ?? null;
  }

  function estimateDelivery(product) {
    if (!product) return null;

    const buyerLatValue = $("#delivery-latitude")?.value;
    const buyerLonValue = $("#delivery-longitude")?.value;

    if (
      !buyerLatValue ||
      !buyerLonValue
    ) {
      return null;
    }

    const buyerLat = Number(buyerLatValue);
    const buyerLon = Number(buyerLonValue);
    const storeLat = product.store_latitude;
    const storeLon = product.store_longitude;

    if (
      !Number.isFinite(buyerLat) ||
      !Number.isFinite(buyerLon) ||
      !Number.isFinite(storeLat) ||
      !Number.isFinite(storeLon)
    ) {
      return null;
    }

    const toRadians = (degrees) => degrees * Math.PI / 180;
    const dLat = toRadians(storeLat - buyerLat);
    const dLon = toRadians(storeLon - buyerLon);

    const a =
      Math.sin(dLat / 2) ** 2 +
      Math.cos(toRadians(buyerLat)) *
        Math.cos(toRadians(storeLat)) *
        Math.sin(dLon / 2) ** 2;

    const distance = 6371 * 2 * Math.atan2(
      Math.sqrt(Math.max(0, Math.min(1, a))),
      Math.sqrt(1 - Math.max(0, Math.min(1, a))),
    );

    return {
      distance,
      fee: 2000 + Math.floor(distance / 0.5 + 1e-9) * 1000,
    };
  }

  function getDeliveryFee() {
    if (cart.quantity(activeStoreId) === 0) return 0;
    if (selectedFulfillment() !== "delivery") return 0;
  
    const product = selectedStore();
  
    if (!product) return null;
    if (product.store_free_delivery) return 0;
  
    const estimate = estimateDelivery(product);
  
    if (!estimate) return null;
  
    const discount = Math.min(
      Math.max(0, Number(product.store_delivery_discount) || 0),
      2000,
    );
  
    return estimate.fee - discount;
  }
  
  function renderCart() {
    cart.render({
      ...cartView,
      storeId:
        activeCartStage === "checkout"
          ? activeStoreId
          : null,
      showCheckout: activeCartStage === "selection",
    });
  
    if (cartCountItems) {
      cartCountItems.textContent = String(cart.quantity());
    }
    
    const deliveryInput = document.querySelector(
      'input[name="fulfillment-method"][value="delivery"]',
    );
    
    const deliveryOption = deliveryInput?.closest(
      ".fulfillment-option",
    );
    
    const deliveryNotice = $("#delivery-unavailable-note");
    
    if (deliveryAvailable !== null) {
      const isUnavailable = !deliveryAvailable;
    
      if (deliveryOption) {
        deliveryOption.hidden = isUnavailable;
      }
    
      if (deliveryNotice) {
        deliveryNotice.hidden = !isUnavailable;
      }
    
      if (isUnavailable && selectedFulfillment() === "delivery") {
        const pickupInput = document.querySelector(
          'input[name="fulfillment-method"][value="pickup"]',
        );
    
        if (pickupInput) {
          pickupInput.checked = true;
        }
    
        toggleDeliveryFields();
      }
    }

    const cartCountLabel = $("#cart-count-label");

    if (cartCountLabel) {
      cartCountLabel.textContent = String(cart.quantity());
    }

    const deliveryFee = getDeliveryFee();
    const subtotal = cart.total(activeStoreId);
    
    const product = selectedStore();
    const estimate = estimateDelivery(product);
    const deliveryOptionPrice = $("#delivery-option-price");

    if (deliveryOptionPrice) {
      if (product?.store_free_delivery) {
        deliveryOptionPrice.innerHTML = estimate
          ? `
            <span class="delivery-price-original">
              ${formatRupiah(estimate.fee)}
            </span>
            <span class="delivery-price-discount">
              Gratis
            </span>
          `
          : "Gratis";
      } else if (estimate) {
        const discount = Math.min(
          Math.max(
            0,
            Number(product?.store_delivery_discount) || 0,
          ),
          2000,
        );

        const finalFee = estimate.fee - discount;

        if (discount > 0) {
          deliveryOptionPrice.innerHTML = `
            <span class="delivery-price-original">
              ${formatRupiah(estimate.fee)}
            </span>
            <span class="delivery-price-discount">
              ${formatRupiah(finalFee)}
            </span>
          `;
        } else {
          deliveryOptionPrice.textContent = formatRupiah(estimate.fee);
        }
      } else {
        deliveryOptionPrice.textContent = "Pilih lokasi";
      }
    }

    const deliveryFeeElement = $("#delivery-fee");

    if (deliveryFeeElement) {
      deliveryFeeElement.textContent =
        deliveryFee === null
          ? "Pilih lokasi"
          : formatRupiah(deliveryFee);
    }

    const totalElement = $("#cart-total");

    if (totalElement) {
      totalElement.textContent = formatRupiah(
        subtotal + (deliveryFee ?? 0),
      );
    }

    const fulfillmentNote = $("#fulfillment-note");

    if (fulfillmentNote) {
      if (selectedFulfillment() === "pickup") {
        fulfillmentNote.textContent =
          "Ambil sendiri di warung tanpa biaya pengantaran.";
      } else if (product?.store_free_delivery) {
        fulfillmentNote.textContent =
          "Warung memberikan gratis ongkir.";
      } else if (
        product &&
        Number(product.store_delivery_discount) > 0 &&
        estimate
      ) {
        fulfillmentNote.textContent =
          `Diskon ongkir ${formatRupiah(
            Math.min(
              Number(product.store_delivery_discount),
              estimate.fee,
            ),
          )}. Ongkir final dihitung saat checkout.`;
      } else if (!estimate) {
        fulfillmentNote.textContent =
          "Pilih lokasi GPS untuk menghitung ongkir.";
      } else {
        fulfillmentNote.textContent =
          `Jarak garis lurus sekitar ${estimate.distance.toFixed(2)} km. Ongkir final dihitung saat checkout.`;
      }
    }

    if (checkoutButton) {
      checkoutButton.disabled =
        !catalogLoaded || cart.quantity(activeStoreId) === 0;
    }
  }

  function addToCart(productId) {
    const product = products.find(
      (item) => Number(item.id) === Number(productId),
    );
  
    if (!product || !product.available) {
      showToast(toast, "Produk tidak tersedia.");
      return;
    }
  
    const addedProduct = cart.add(productId);
  
    if (!addedProduct) {
      showToast(
        toast,
        "Jumlah produk sudah mencapai batas atau produk tidak tersedia.",
      );
      return;
    }
  
    onCatalogRender?.();
    renderCart();
  
    showToast(
      toast,
      `${product.name} ditambahkan ke keranjang.`,
    );
  }
  
  function setSheetTitle(value) {
    const activeSheet = checkoutSheet.isOpen()
      ? checkoutSheet
      : cartSheet;
  
    activeSheet.setTitle(value);
  }
  
  function setCartStage(stage) {
    activeCartStage = stage;
  
    const isSelection = stage === "selection";
  
    const checkoutSections = [
      ".checkout-form",
      ".buyer-note-section",
      ".fulfillment-section",
      ".order-summary",
      ".checkout-actions",
    ];
  
    checkoutSections.forEach((selector) => {
      const element = cartOverlay.querySelector(selector);
  
      if (element) {
        element.hidden = isSelection;
      }
    });
  
    const productsHeading = cartOverlay.querySelector(
      ".cart-products-section .cart-section-heading h3",
    );
  
    if (productsHeading) {
      productsHeading.textContent = isSelection
        ? "Daftar Warung"
        : "Item Pesanan";
    }
  
    setSheetTitle(
      isSelection ? "Pilih Warung" : "Checkout",
    );
  }
  
  function showConfirmation() {
    const entries = cart.entries(activeStoreId);
  
    if (entries.length === 0) {
      showToast(toast, "Keranjang warung ini masih kosong.");
      return;
    }
  
    const product = selectedStore();
  
    if (!product) {
      showToast(toast, "Warung pada keranjang tidak ditemukan.");
      return;
    }
  
    if (!product.store_is_open) {
      showToast(toast, "Warung sedang tutup.");
      return;
    }
  
    // GPS wajib tersedia untuk semua pesanan Delivery.
    if (selectedFulfillment() === "delivery") {
      const latitude = $("#delivery-latitude")?.value.trim() ?? "";
      const longitude = $("#delivery-longitude")?.value.trim() ?? "";
  
      if (!latitude || !longitude) {
        gps.showRequiredError();
        return;
      }
    }
  
    gps.clearError();
  
    const rows = entries.map((entry) => {
      const item = products.find(
        (product) =>
          Number(product.id) === Number(entry.product_id),
      );
  
      const quantity = Number(entry.quantity) || 0;
      const price = Number(item?.price) || 0;
      const variant = String(item?.variant || "").trim();
  
      return {
        name: item?.name || "Produk",
        variant,
        quantity,
        price,
        total: quantity * price,
      };
    });
  
    confirmationItems.replaceChildren();
  
    rows.forEach((item) => {
      const row = document.createElement("tr");
  
      const nameCell = document.createElement("td");
      nameCell.textContent = item.variant
        ? `${item.name} (${item.variant})`
        : item.name;
  
      const quantityCell = document.createElement("td");
      quantityCell.textContent = String(item.quantity);
  
      const priceCell = document.createElement("td");
      priceCell.textContent = formatRupiah(item.price);
  
      const totalCell = document.createElement("td");
      totalCell.textContent = formatRupiah(item.total);
  
      row.append(
        nameCell,
        quantityCell,
        priceCell,
        totalCell,
      );
  
      confirmationItems.append(row);
    });
  
    const subtotal = cart.total(activeStoreId);
    const deliveryFee = getDeliveryFee();
    const isDelivery = selectedFulfillment() === "delivery";
  
    $("#confirmation-subtotal").textContent =
      formatRupiah(subtotal);
  
    $("#confirmation-delivery-fee").textContent =
      deliveryFee === null
        ? "Belum dihitung"
        : formatRupiah(deliveryFee);
  
    $("#confirmation-total-label").textContent =
      deliveryFee === null
        ? "Total sementara"
        : "Total Pesanan";
  
    $("#confirmation-total").textContent =
      deliveryFee === null
        ? "Menunggu lokasi"
        : formatRupiah(subtotal + deliveryFee);
  
    $("#confirmation-delivery-note").hidden =
      !isDelivery || deliveryFee !== null;
  
    cartFooter.hidden = true;
    confirmationPanel.hidden = false;
    successPanel.hidden = true;
  
    $("#cart-empty").hidden = true;
  
    setSheetTitle("Konfirmasi Pesanan");
  }
  
  function showCartEditor() {
    confirmationPanel.hidden = true;
    successPanel.hidden = true;
  
    setCartStage("checkout");
  
    cartFooter.hidden = false;
  
    $("#cart-empty").hidden =
      cart.quantity(activeStoreId) > 0;
  
    renderCart();
  }
  
  function showOrderSuccess() {
    cartFooter.hidden = true;
    confirmationPanel.hidden = true;
    successPanel.hidden = false;
  
    $("#cart-empty").hidden = true;
  
    setSheetTitle("Pesanan Berhasil");
  }

  function openCart() {
    if (
      !cartOverlay ||
      cartSheet.isOpen() ||
      checkoutSheet.isOpen()
    ) {
      return;
    }
  
    activeStoreId = null;
  
    setCartStage("selection");
  
    cartOverlay.hidden = false;
  
    renderCart();
  
    cartSheet.open({
      title: "Pilih Warung",
      content: cartOverlay,
      size: "full",
      closeOnBackdrop: true,
      closeButton: true,
      onClose: () => {
        if (openCheckoutAfterCartClose) {
          openCheckoutAfterCartClose = false;
          openCheckoutSheet();
          return;
        }
    
        cartOverlay.hidden = true;
      },
    });
  }
  
  function openCheckoutSheet() {
    if (!activeStoreId) return;
  
    cartOverlay.hidden = false;
  
    setCartStage("checkout");
  
    cartFooter.hidden = false;
    confirmationPanel.hidden = true;
    successPanel.hidden = true;
  
    renderCart();
  
    checkoutSheet.open({
      title: "Checkout",
      content: cartOverlay,
      size: "large",
      closeOnBackdrop: true,
      closeButton: true,
  
      onClose: () => {
        cartOverlay.hidden = true;
  
        activeStoreId = null;
  
        setCartStage("selection");
  
        renderCart();
  
        if (
          reopenCartAfterCheckoutClose &&
          cart.quantity() > 0
        ) {
          reopenCartAfterCheckoutClose = false;
  
          openCart();
        }
      },
    });
  }
  
  function selectStoreForCheckout(storeId) {
    const selectedId = Number(storeId);
  
    if (
      !Number.isSafeInteger(selectedId) ||
      selectedId <= 0
    ) {
      showToast(toast, "Warung tidak valid.");
      return;
    }
  
    const entries = cart.entries(selectedId);
  
    if (entries.length === 0) {
      showToast(toast, "Keranjang warung ini kosong.");
      return;
    }
  
    const product = products.find(
      (item) =>
        Number(item.store_id) === selectedId &&
        entries.some(
          (entry) =>
            Number(entry.product_id) === Number(item.id),
        ),
    );
  
    if (!product) {
      showToast(toast, "Produk warung tidak ditemukan.");
      return;
    }
  
    if (!product.store_is_open) {
      showToast(toast, "Warung sedang tutup.");
      return;
    }
  
    activeStoreId = selectedId;
  
    if (cartSheet.isOpen()) {
      openCheckoutAfterCartClose = true;
      cartSheet.close();
      return;
    }
  
    openCheckoutSheet();
  }
  
  function closeCart() {
    if (checkoutSheet.isOpen()) {
      checkoutSheet.close();
      return;
    }
  
    if (cartSheet.isOpen()) {
      cartSheet.close();
    }
  }

  async function loadBuyerProfile() {
    try {
      const response = await fetch("/api/buyer-profile.php", {
        method: "GET",
        credentials: "same-origin",
        cache: "no-store",
        headers: {
          Accept: "application/json",
        },
      });
  
      if (!response.ok) return;
  
      const result = await response.json();
  
      if (!result.success || !result.profile) return;
  
      const profile = result.profile;
  
      const nameInput = $("#buyer-name");
      const phoneInput = $("#buyer-phone");
      const addressInput = $("#delivery-address");
      const mapsInput = $("#delivery-maps-url");
      const latitudeInput = $("#delivery-latitude");
      const longitudeInput = $("#delivery-longitude");
  
      // Jangan menimpa data yang sudah mulai diketik pembeli.
      if (nameInput && !nameInput.value.trim()) {
        nameInput.value = profile.name || "";
      }
  
      if (phoneInput && !phoneInput.value.trim()) {
        phoneInput.value = profile.phone || "";
      }
  
      if (addressInput && !addressInput.value.trim()) {
        addressInput.value = profile.delivery_address || "";
      }
  
      if (mapsInput && !mapsInput.value.trim()) {
        mapsInput.value = profile.delivery_maps_url || "";
      }
  
      if (
        latitudeInput &&
        !latitudeInput.value &&
        profile.delivery_latitude !== null
      ) {
        latitudeInput.value = profile.delivery_latitude;
      }
  
      if (
        longitudeInput &&
        !longitudeInput.value &&
        profile.delivery_longitude !== null
      ) {
        longitudeInput.value = profile.delivery_longitude;
      }
  
      if (
        latitudeInput?.value &&
        longitudeInput?.value
      ) {
        gps.resetStatus();
      }
  
      renderCart();
  
    } catch (error) {
      // Checkout tetap dapat digunakan meskipun profil gagal dimuat.
      console.warn(
        "[Pesan Ajah] Profil pembeli tidak dapat dimuat.",
        error,
      );
    }
  }

  function toggleDeliveryFields() {
      const section = $("#delivery-address-section");
      const phoneField = $("#buyer-phone-field");
      const isDelivery = selectedFulfillment() === "delivery";
  
      if (section) section.hidden = !isDelivery;
      if (phoneField) phoneField.hidden = !isDelivery;
  
      const address = $("#delivery-address");
  
      if (address) {
          address.required = false;
      }
  
      const latitude = $("#delivery-latitude");
      const longitude = $("#delivery-longitude");
  
      if (
          !isDelivery ||
          (latitude?.value && longitude?.value)
      ) {
          gps.clearError();
      }
  
      gps.resetStatus();
  }

  function validMapsUrl(value) {
    if (!value) return true;

    try {
      const url = new URL(value);
      const host = url.hostname.toLowerCase();

      const allowedHosts = [
        "google.com",
        "www.google.com",
        "maps.google.com",
        "maps.app.goo.gl",
        "goo.gl",
      ];

      return (
        url.protocol === "https:" &&
        (
          allowedHosts.includes(host) ||
          host.endsWith(".google.com")
        )
      );
    } catch {
      return false;
    }
  }

  async function submitCheckout() {
      const checkoutEntries = cart.entries(activeStoreId);
      
      if (checkoutEntries.length === 0) {
        showToast(toast, "Keranjang warung ini masih kosong.");
        return;
      }
  
      const product = selectedStore();
  
      if (!product) {
          showToast(toast, "Warung pada keranjang tidak ditemukan.");
          return;
      }
  
      if (!product.store_is_open) {
          showToast(toast, "Warung sedang tutup.");
          return;
      }
  
      const buyerName = $("#buyer-name")?.value.trim() ?? "";
      const buyerPhone = $("#buyer-phone")?.value.trim() ?? "";
      const deliveryMethod = selectedFulfillment();
  
      if (
          deliveryMethod === "delivery" &&
          deliveryAvailable === false
      ) {
          showToast(
              toast,
              "Layanan Delivery sedang offline. Silakan pilih Ambil Sendiri.",
          );
          return;
      }
  
      const deliveryAddress =
          $("#delivery-address")?.value.trim() ?? "";
  
      const deliveryMapsUrl =
          $("#delivery-maps-url")?.value.trim() ?? "";
  
      const deliveryLatitude =
          $("#delivery-latitude")?.value.trim() ?? "";
  
      const deliveryLongitude =
          $("#delivery-longitude")?.value.trim() ?? "";
      
      if (deliveryMethod === "delivery") {
          const buyerLat = Number(deliveryLatitude);
          const buyerLon = Number(deliveryLongitude);

          if (!deliveryLatitude || !deliveryLongitude) {
              gps.showRequiredError();

              showToast(
                  toast,
                  "Atur lokasi GPS terlebih dahulu.",
              );

              return;
          }

          if (
              !Number.isFinite(buyerLat) ||
              !Number.isFinite(buyerLon) ||
              buyerLat < -90 ||
              buyerLat > 90 ||
              buyerLon < -180 ||
              buyerLon > 180
          ) {
              showToast(
                  toast,
                  "Koordinat lokasi pembeli tidak valid. Atur GPS kembali.",
              );

              return;
          }

          if (
              !product.store_free_delivery &&
              (
                  !Number.isFinite(Number(product.store_latitude)) ||
                  !Number.isFinite(Number(product.store_longitude))
              )
          ) {
              showToast(
                  toast,
                  "Lokasi warung belum valid. Periksa pengaturan lokasi warung.",
              );

              return;
          }
      }
  
      if (!buyerName || buyerName.length > 100) {
          showToast(toast, "Nama pembeli wajib diisi.");
          $("#buyer-name")?.focus();
          return;
      }
  
      if (
          deliveryMethod === "delivery" &&
          (!buyerPhone || buyerPhone.length > 25)
      ) {
          showToast(toast, "Nomor WhatsApp belum kamu isi.");
          $("#buyer-phone")?.focus();
          return;
      }
  
      if (
          deliveryMethod === "delivery" &&
          deliveryAddress.length > 255
      ) {
          showToast(toast, "Detail alamat maksimal 255 karakter.");
          $("#delivery-address")?.focus();
          return;
      }
  
      if (
          deliveryMethod === "delivery" &&
          !validMapsUrl(deliveryMapsUrl)
      ) {
          showToast(
              toast,
              "Tautan lokasi harus berupa tautan Google Maps yang valid.",
          );
          $("#delivery-maps-url")?.focus();
          return;
      }
  
      if (
          (deliveryLatitude && !deliveryLongitude) ||
          (!deliveryLatitude && deliveryLongitude)
      ) {
          showToast(toast, "Koordinat lokasi tidak lengkap.");
          return;
      }
  
      const confirmButton = $("#confirm-order-button");
      const originalText = confirmButton?.textContent || "Yakin dan Lanjutkan";
      
      if (confirmButton) {
          confirmButton.disabled = true;
          confirmButton.textContent = "Memproses...";
      }
  
      const pendingStorageKey = "pesan_ajah_pending_checkout";
      const orderStorageKey = "pesan_ajah_order_tokens";
  
      try {
          const payload = {
              _token: "",
              store_id: product.store_id,
              buyer_name: buyerName,
              buyer_phone: buyerPhone,
              buyer_note: $("#buyer-note")?.value.trim() ?? "",
              delivery_method: deliveryMethod,
  
              delivery_address:
                  deliveryMethod === "delivery" ? deliveryAddress : "",
  
              delivery_maps_url:
                  deliveryMethod === "delivery" ? deliveryMapsUrl : "",
  
              delivery_latitude:
                  deliveryMethod === "delivery" ? deliveryLatitude : "",
  
              delivery_longitude:
                  deliveryMethod === "delivery" ? deliveryLongitude : "",
  
              items: checkoutEntries,
          };
  
          /*
           * Fingerprint digunakan untuk memastikan draft lama
           * hanya digunakan kembali jika isi checkout masih sama.
           */
          const fingerprintPayload = { ...payload };
          delete fingerprintPayload._token;
  
          const fingerprint = JSON.stringify(fingerprintPayload);
  
          let pendingOrder = null;
  
          try {
              const storedPending = JSON.parse(
                  localStorage.getItem(pendingStorageKey) || "null",
              );
  
              if (
                  storedPending &&
                  storedPending.fingerprint === fingerprint &&
                  typeof storedPending.order_number === "string" &&
                  typeof storedPending.buyer_token === "string" &&
                  typeof storedPending.whatsapp_url === "string"
              ) {
                  pendingOrder = storedPending;
              }
          } catch {
              pendingOrder = null;
          }
  
          let result;
  
          if (pendingOrder) {
              /*
               * Gunakan draft sebelumnya.
               * Jangan membuat pesanan baru jika aktivasi sebelumnya gagal.
               */
              result = {
                  order_number: pendingOrder.order_number,
                  buyer_token: pendingOrder.buyer_token,
                  whatsapp_url: pendingOrder.whatsapp_url,
              };
          } else {
              result = await sendCheckoutRequest(payload);
          }
  
          if (
              typeof result.order_number !== "string" ||
              typeof result.buyer_token !== "string" ||
              !/^[a-f0-9]{64}$/i.test(result.buyer_token)
          ) {
              throw new Error(
                  "Data token pesanan tidak valid. Simpan nomor pesanan: " +
                  (result.order_number || "-"),
              );
          }
  
          let whatsappUrl;
  
          try {
              whatsappUrl = new URL(result.whatsapp_url);
          } catch {
              throw new Error("Tautan WhatsApp tidak valid.");
          }
  
          if (
              whatsappUrl.protocol !== "https:" ||
              ![
                  "wa.me",
                  "api.whatsapp.com",
                  "web.whatsapp.com",
              ].includes(whatsappUrl.hostname)
          ) {
              throw new Error("Tujuan WhatsApp tidak valid.");
          }
  
          /*
           * Simpan draft sementara sebelum aktivasi.
           * Jika aktivasi gagal, percobaan berikutnya dapat
           * melanjutkan pesanan yang sama.
           */
          pendingOrder = {
              fingerprint,
              order_number: result.order_number,
              buyer_token: result.buyer_token,
              whatsapp_url: whatsappUrl.href,
              created_at: new Date().toISOString(),
          };
  
          try {
              localStorage.setItem(
                  pendingStorageKey,
                  JSON.stringify(pendingOrder),
              );
          } catch (error) {
              throw new Error(
                  "Draft pesanan tidak dapat disimpan. " +
                  "Periksa penyimpanan browser dan coba kembali.",
              );
          }
  
          /*
           * Simpan token buyer satu kali.
           * Data ini digunakan untuk riwayat/pelacakan pesanan.
           */
          let savedOrders = [];
  
          try {
              const storedOrders = JSON.parse(
                  localStorage.getItem(orderStorageKey) || "[]",
              );
  
              if (Array.isArray(storedOrders)) {
                  savedOrders = storedOrders;
              }
          } catch {
              savedOrders = [];
          }
  
          savedOrders = savedOrders.filter(
              (order) =>
                  order &&
                  order.order_number !== result.order_number,
          );
  
          savedOrders.unshift({
              token: result.buyer_token,
              order_number: result.order_number,
              created_at: new Date().toISOString(),
          });
  
          try {
              localStorage.setItem(
                  orderStorageKey,
                  JSON.stringify(savedOrders.slice(0, 50)),
              );
          } catch (error) {
              console.warn(
                  "[Pesan Ajah] Token riwayat pesanan tidak dapat disimpan.",
                  error,
              );
          }
  
          /*
           * Aktifkan pesanan.
           * Endpoint ini aman dipanggil ulang untuk pesanan
           * yang sudah berstatus pending.
           */
          const activationResponse = await fetch(
              "/api/activate-order.php",
              {
                  method: "POST",
                  credentials: "same-origin",
                  cache: "no-store",
                  headers: {
                      "Content-Type": "application/json",
                      Accept: "application/json",
                  },
                  body: JSON.stringify({
                      order_number: result.order_number,
                      buyer_token: result.buyer_token,
                  }),
              },
          );
  
          const activationResult = await activationResponse.json();
  
          if (
              !activationResponse.ok ||
              !activationResult.success ||
              activationResult.status !== "pending"
          ) {
              throw new Error(
                  activationResult.message ||
                  "Pesanan belum berhasil diaktifkan. Silakan coba lagi.",
              );
          }
  
          /*
           * Hapus draft sementara setelah aktivasi berhasil.
           */
          try {
              const storedPending = JSON.parse(
                  localStorage.getItem(pendingStorageKey) || "null",
              );
  
              if (
                  storedPending?.order_number === result.order_number
              ) {
                  localStorage.removeItem(pendingStorageKey);
              }
          } catch (error) {
              console.warn(
                  "[Pesan Ajah] Draft sementara tidak dapat dibersihkan.",
                  error,
              );
          }
  
          cart.clearStore(activeStoreId);
          
          renderCart();
          onCatalogRender?.();
          
          /*
           * Tampilkan sheet berhasil sebelum berpindah ke WhatsApp.
           */
          showOrderSuccess();
          
          /*
           * Arahkan tab aktif ke WhatsApp Seller.
           * Tidak membuat tab kosong atau tab baru.
           */
          window.location.assign(whatsappUrl.href);
  
      } catch (error) {
          showToast(
              toast,
              error instanceof Error
                  ? error.message
                  : "Terjadi kesalahan saat checkout.",
          );
      
      } finally {
          if (confirmButton) {
              confirmButton.disabled = false;
              confirmButton.textContent = originalText;
          }
      }
  }

  cartButton?.addEventListener("click", openCart);
  closeCartButton?.addEventListener("click", closeCart);
  $("#empty-cart-close")?.addEventListener("click", closeCart);

  fulfillmentInputs.forEach((input) => {
    input.addEventListener("change", () => {
      toggleDeliveryFields();
      renderCart();
    });
  });

  toggleDeliveryFields();
  
  loadBuyerProfile();
  
  cartItems?.addEventListener("click", (event) => {
    const addButton = event.target.closest("[data-add]");
    const removeButton = event.target.closest("[data-remove]");
    const checkoutStoreButton = event.target.closest(
      "[data-checkout-store]",
    );
  
    if (checkoutStoreButton) {
      selectStoreForCheckout(
        checkoutStoreButton.dataset.checkoutStore,
      );
  
      return;
    }
  
    if (addButton) {
      addToCart(Number(addButton.dataset.add));
      return;
    }
  
    if (removeButton) {
      cart.change(
        Number(removeButton.dataset.remove),
        -1,
      );
  
      onCatalogRender?.();
      renderCart();
    }
  });

  checkoutButton?.addEventListener(
    "click",
    showConfirmation,
  );
  
  $("#edit-cart-button")?.addEventListener(
    "click",
    showCartEditor,
  );
  
  $("#confirm-order-button")?.addEventListener(
      "click",
      submitCheckout,
  );
  
  $("#success-back-button")?.addEventListener("click", () => {
    reopenCartAfterCheckoutClose = true;
  
    checkoutSheet.close();
  });

  renderCart();

  return {
    addToCart,
    render: renderCart,
  
    setCatalogLoaded(value) {
      catalogLoaded = Boolean(value);
      renderCart();
    },
  
    setDeliveryAvailable(value) {
      deliveryAvailable = Boolean(value);
      renderCart();
      toggleDeliveryFields();
    },
  };
}
