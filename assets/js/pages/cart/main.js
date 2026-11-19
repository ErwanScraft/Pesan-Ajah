import { createBottomSheet } from "../../modules/bottom-sheet.js";

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
  const cartSheet = createBottomSheet();
  const cartFooter = $("#cart-footer");
  const confirmationPanel = $("#cart-confirmation");
  const successPanel = $("#cart-order-success");
  const confirmationItems = $("#confirmation-items");

  const fulfillmentInputs = document.querySelectorAll(
    'input[name="fulfillment-method"]',
  );

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

  function selectedFulfillment() {
    return document.querySelector(
      'input[name="fulfillment-method"]:checked',
    )?.value ?? "delivery";
  }

  function selectedStore() {
    const entry = cart.entries()[0];

    if (!entry) return null;

    return products.find(
      (product) => Number(product.id) === entry.product_id,
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
    if (cart.quantity() === 0) return 0;
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
    cart.render(cartView);
  
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
    const subtotal = cart.total();

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
        !catalogLoaded || cart.quantity() === 0;
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

    const currentStore = selectedStore();

    if (
      currentStore &&
      Number(currentStore.store_id) !== Number(product.store_id)
    ) {
      showToast(
        toast,
        "Keranjang hanya bisa berisi produk dari satu warung. Selesaikan atau kosongkan keranjang terlebih dahulu.",
      );
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
      const title = $("#app-bottom-sheet-title");
  
      if (title) {
          title.textContent = value;
      }
  }
  
  function showConfirmation() {
  
      if (cart.quantity() === 0) {
          showToast(toast, "Keranjang masih kosong.");
          return;
      }
  
      const rows = cart.entries().map((entry) => {
          const item = products.find(
              (product) =>
                  Number(product.id) === Number(entry.product_id),
          );
  
          const quantity = Number(entry.quantity) || 0;
          const price = Number(item?.price) || 0;
  
          return {
              name: item?.name || "Produk",
              quantity,
              price,
              total: quantity * price,
          };
      });
  
      confirmationItems.replaceChildren();
  
      rows.forEach((item) => {
          const row = document.createElement("tr");
  
          [
              item.name,
              String(item.quantity),
              formatRupiah(item.price),
              formatRupiah(item.total),
          ].forEach((value) => {
              const cell = document.createElement("td");
              cell.textContent = value;
              row.append(cell);
          });
  
          confirmationItems.append(row);
      });
  
      const deliveryFee = getDeliveryFee();
  
      $("#confirmation-total").textContent = formatRupiah(
          cart.total() + (deliveryFee ?? 0),
      );
  
      cartFooter.hidden = true;
      confirmationPanel.hidden = false;
      successPanel.hidden = true;
  
      setSheetTitle("Konfirmasi Pesanan");
      
      $("#cart-empty").hidden = true;
  }
  
  function showCartEditor() {
      confirmationPanel.hidden = true;
      successPanel.hidden = true;
  
      cartFooter.hidden = false;
  
      setSheetTitle("Keranjang");
      
      $("#cart-empty").hidden = cart.quantity() > 0;
  }
  
  function showOrderSuccess() {
      cartFooter.hidden = true;
      confirmationPanel.hidden = true;
  
      successPanel.hidden = false;
  
      setSheetTitle("Pesanan Berhasil");
      
      $("#cart-empty").hidden = true;
  }

  function openCart() {
    if (!cartOverlay || cartSheet.isOpen()) return;
  
    cartOverlay.hidden = false;
  
    cartSheet.open({
      title: "Keranjang",
      content: cartOverlay,
      size: "large",
      closeOnBackdrop: true,
      closeButton: true,
      onClose: () => {
        cartOverlay.hidden = true;
      },
    });
  }
  
  function closeCart() {
    if (!cartSheet.isOpen()) return;
  
    cartSheet.close();
  }

  async function loadCsrfToken() {
    const response = await fetch("/api/csrf.php", {
      method: "GET",
      credentials: "same-origin",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    });

    const data = await response.json();

    if (!response.ok || !data.success || !data.csrf_token) {
      throw new Error(
        data.message || "Token keamanan tidak dapat dimuat.",
      );
    }

    csrfToken = data.csrf_token;
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
        setLocationStatus("success");
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

  function setLocationStatus(state = "idle") {
    const button = $("#use-location-button");
  
    if (!button) return;
  
    const labels = {
      idle: "Pilih lokasi GPS",
      loading: "Sedang mencari lokasi",
      success: "Lokasi berhasil dipilih",
      error: "Gagal memilih lokasi, coba lagi",
    };
  
    const label = labels[state] || labels.idle;
  
    button.setAttribute("aria-label", label);
    button.title = label;
    button.dataset.locationState = state;
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
    
    setLocationStatus(
      latitude?.value && longitude?.value
        ? "success"
        : "idle",
    );
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

  async function useCurrentLocation() {
    if (!navigator.geolocation) {
      setLocationStatus("error");
      showToast(
        toast,
        "Browser tidak mendukung akses lokasi.",
      );
      return;
    }

    const button = $("#use-location-button");

    if (button) {
      button.disabled = true;
    }

    setLocationStatus("loading");

    try {
      const position = await new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(
          resolve,
          reject,
          {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0,
          },
        );
      });

      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;

      $("#delivery-latitude").value = latitude.toFixed(7);
      $("#delivery-longitude").value = longitude.toFixed(7);

      $("#delivery-maps-url").value =
        `https://maps.google.com/?q=${latitude},${longitude}`;

      setLocationStatus("success");
      renderCart();
    } catch (error) {
      let message = "Lokasi tidak berhasil diperoleh.";

      if (error?.code === 1) {
        message =
          "Izin lokasi ditolak. Aktifkan izin lokasi atau coba lagi.";
      } else if (error?.code === 2) {
        message =
          "Lokasi tidak tersedia. Periksa GPS perangkat Anda.";
      } else if (error?.code === 3) {
        message =
          "Pengambilan lokasi melewati batas waktu. Silakan coba lagi.";
      }

      setLocationStatus("error");
      showToast(toast, message);
    } finally {
      if (button) {
        button.disabled = false;
      }
    }
  }

  async function submitCheckout(whatsappWindow = null) {
    if (cart.quantity() === 0) {
      showToast(toast, "Keranjang masih kosong.");
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
      deliveryMethod === "delivery"
      && deliveryAvailable === false
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

    if (
      deliveryMethod === "delivery" &&
      !product.store_free_delivery
    ) {
      const buyerLat = Number(deliveryLatitude);
      const buyerLon = Number(deliveryLongitude);

      if (!deliveryLatitude || !deliveryLongitude) {
        showToast(
          toast,
          "Pilih lokasi GPS pengantaran untuk menghitung ongkir.",
        );
        return;
      }

      if (
        !Number.isFinite(buyerLat) ||
        !Number.isFinite(buyerLon) ||
        !Number.isFinite(product.store_latitude) ||
        !Number.isFinite(product.store_longitude)
      ) {
        showToast(
          toast,
          "Lokasi warung atau pembeli belum valid. Periksa pengaturan lokasi.",
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
      showToast(toast, "Nomor Whatsapp belum kamu isi lo.");
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

    checkoutButton.disabled = true;
    
    const confirmButton = $("#confirm-order-button");
    
    if (confirmButton) {
        confirmButton.disabled = true;
        confirmButton.textContent = "Memproses...";
    }
    
    const originalText = checkoutButton.textContent;
    
    checkoutButton.textContent = "Menyimpan pesanan...";

    try {
      if (!csrfToken) {
        await loadCsrfToken();
      }

      const payload = {
        _token: csrfToken,
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

        items: cart.entries(),
      };

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

      const result = await response.json();

      if (!response.ok || !result.success) {
        throw new Error(
          result.message || "Checkout gagal diproses.",
        );
      }

      if (
        typeof result.buyer_token !== "string" ||
        !/^[a-f0-9]{64}$/i.test(result.buyer_token)
      ) {
        throw new Error(
          "Pesanan tersimpan, tetapi token pelacakan tidak diterima. Simpan nomor pesanan: " +
          result.order_number,
        );
      }

      const storageKey = "pesan_ajah_order_tokens";
      let savedOrders = [];

      try {
        const stored = JSON.parse(
          localStorage.getItem(storageKey) || "[]",
        );

        if (Array.isArray(stored)) {
          savedOrders = stored;
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

      savedOrders = savedOrders.slice(0, 50);

      try {
        localStorage.setItem(
          storageKey,
          JSON.stringify(savedOrders),
        );
      } catch {
        showToast(
          toast,
          "Pesanan tersimpan, tetapi browser tidak dapat menyimpan riwayat. Buka Dashboard Buyer setelah checkout ini.",
        );
      }

      if (
          typeof result.whatsapp_url !== "string" ||
          !result.whatsapp_url
      ) {
          throw new Error(
              "Tautan WhatsApp Seller tidak tersedia.",
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
      
      // Aktifkan pesanan sebelum membuka WhatsApp.
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
              "Pesanan belum berhasil diaktifkan.",
          );
      }
      
      // Buka WhatsApp Seller.
      if (whatsappWindow && !whatsappWindow.closed) {
          whatsappWindow.location.href = whatsappUrl.href;
      } else {
          // Popup diblokir: gunakan halaman saat ini sebagai fallback.
          window.location.assign(whatsappUrl.href);
          return;
      }
      
      // Simpan token pesanan seperti mekanisme sebelumnya.
      const storageKey = "pesan_ajah_order_tokens";
      
      let savedOrders = [];
      
      try {
          const stored = JSON.parse(
              localStorage.getItem(storageKey) || "[]",
          );
      
          if (Array.isArray(stored)) {
              savedOrders = stored;
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
      
      savedOrders = savedOrders.slice(0, 50);
      
      try {
          localStorage.setItem(
              storageKey,
              JSON.stringify(savedOrders),
          );
      } catch (error) {
          console.warn(
              "[Pesan Ajah] Riwayat pesanan tidak dapat disimpan.",
              error,
          );
      }
      
      // Kosongkan keranjang setelah pesanan berhasil diproses.
      cart.clear();
      
      renderCart();
      onCatalogRender?.();
      
      // Ubah tampilan bottom sheet.
      showOrderSuccess();

    } catch (error) {
        if (whatsappWindow && !whatsappWindow.closed) {
            whatsappWindow.close();
        }
    
        showToast(
            toast,
            error instanceof Error
                ? error.message
                : "Terjadi kesalahan saat checkout.",
        );
    } finally {
        checkoutButton.disabled =
            !catalogLoaded || cart.quantity() === 0;
    
        checkoutButton.textContent = originalText;
    
        const confirmButton = $("#confirm-order-button");
    
        if (confirmButton) {
            confirmButton.disabled = false;
            confirmButton.textContent = "Yakin dan Lanjutkan";
        }
    }
  }

  cartButton?.addEventListener("click", openCart);
  closeCartButton?.addEventListener("click", closeCart);
  $("#empty-cart-close")?.addEventListener("click", closeCart);

  $("#use-location-button")?.addEventListener(
    "click",
    useCurrentLocation,
  );

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

    if (addButton) {
      addToCart(Number(addButton.dataset.add));
      return;
    }

    if (removeButton) {
      cart.change(Number(removeButton.dataset.remove), -1);
      onCatalogRender?.();
      renderCart();
    }
  });

  checkoutButton?.addEventListener("click", showConfirmation);
  
  $("#edit-cart-button")?.addEventListener(
      "click",
      showCartEditor,
  );
  
  $("#confirm-order-button")?.addEventListener(
      "click",
      () => {
          // Membuka tab sejak interaksi pengguna agar tidak
          // diblokir oleh popup blocker ketika proses checkout selesai.
          const whatsappWindow = window.open("about:blank", "_blank");
  
          submitCheckout(whatsappWindow);
      },
  );
  
  $("#success-back-button")?.addEventListener(
      "click",
      () => {
          showCartEditor();
          closeCart();
      },
  );

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
