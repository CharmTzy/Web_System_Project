(() => {
  const page = document.querySelector('[data-cart-screen]');

  if (!page) {
    return;
  }

  const cartItems = document.querySelector('[data-cart-items]');
  const cartSubtotal = document.querySelector('[data-cart-subtotal]');

  const applyCartSummary = (payload) => {
    if (cartItems) {
      cartItems.textContent = String(payload.total_items ?? payload.count ?? 0);
    }

    if (cartSubtotal) {
      cartSubtotal.textContent = payload.subtotal_formatted || payload.subtotal || '$0.00';
    }
  };

  document.addEventListener('storefront:cart-updated', (event) => {
    applyCartSummary(event.detail || {});
  });

  document.addEventListener('input', (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
      return;
    }

    const input = target.closest('[data-cart-quantity-input]');

    if (!input) {
      return;
    }

    const form = input.closest('form');

    if (form) {
      window.Storefront?.scheduleFormSubmit(form, 360);
    }
  });
})();
